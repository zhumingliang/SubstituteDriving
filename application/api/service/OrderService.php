<?php


namespace app\api\service;


use app\api\model\DriverT;
use app\api\model\FarStateT;
use app\api\model\OrderListT;
use app\api\model\OrderPushT;
use app\api\model\OrderT;
use app\api\model\StartPriceT;
use app\lib\enum\CommonEnum;
use app\lib\enum\OrderEnum;
use app\lib\exception\SaveException;
use GatewayClient\Gateway;
use zml\tp_tools\CalculateUtil;

class OrderService
{
    /**
     * 小程序下单
     */
    public function saveMiniOrder($params)
    {
        $far = $this->prefixFar($params);
        $params['u_id'] = Token::getCurrentUid();
        $params['phone'] = Token::getCurrentTokenVar('phone');
        $params['from'] = OrderEnum::FROM_MINI;
        $params['type'] = OrderEnum::NOT_FIXED_MONEY;
        $params['state'] = OrderEnum::ORDER_NO;
        $params['order_num'] = time();
        $params['far_distance'] = $far['far_distance'];
        $params['far_money'] = $far['far_money'];
        $o_id = $this->saveOrder($params);
        $this->saveOrderList($o_id);
        return $o_id;
    }

    private function prefixFar($params)
    {
        //检查远程接驾是否开启
        $far_state = FarStateT::get();
        if ($far_state->open == 2) {
            return [
                'far_money' => 0,
                'far_distance' => 0,
            ];
        }
        //计算距离
        $far_distance = CalculateUtil::GetDistance($params['start_lng'],
            $params['start_lat'], $params['end_lng'],
            $params['end_lat']);

        $farRule = StartPriceT::where('type', 2)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->order('order')
            ->select();

        if (!count($farRule)) {
            return [
                'far_money' => 0,
                'far_distance' => $far_distance,
            ];
        }

        return [
            'far_money' => $this->prefixStartPriceWithDistance($far_distance, $farRule),
            'far_distance' => $far_distance,
        ];

    }

    private function prefixStartPriceWithDistance($distance, $farRule)
    {
        $money_new = 0;
        $count = count($farRule) - 1;
        foreach ($farRule as $k => $v) {
            if ($distance <= 0) {
                return $money_new;
                break;
            }
            if ($count > $k) {
                $money_new += $v['price'];
                $distance -= $v['distance'];
            } else {
                $money_new += $v['price'] * ceil($distance / $v['distance']);
            }

        }
        return $money_new;

    }


    /**
     * 处理等待推送队列
     */
    public function orderListHandel()
    {
        //查询待处理订单并将订单状态改为处理中
        $orderList = OrderListT::where('state', OrderEnum::ORDER_LIST_NO)
            ->find();
        if (!$orderList) {
            return true;
        }
        $orderList->state = OrderEnum::ORDER_LIST_ING;
        $orderList->save();

        //获取订单信息并检测订单状态
        $order = OrderT::getOrder($orderList->o_id);
        if (!$order || $order->state != OrderEnum::ORDER_NO) {
            $orderList->state = OrderEnum::ORDER_LIST_COMPLETE;
            $orderList->save();
            return true;
        }
        //查找司机并推送
        $this->findDriverToPush($order);

    }

    /**
     * 处理推送列表
     */
    public function handelDriverNoAnswer()
    {
        OrderPushT::where('state', OrderEnum::ORDER_PUSH_NO)
            ->where('create_time', '<', date("Y-m-d H:i:s", time() - config('setting.driver_push_expire_in')))
            ->update(['state' => 4]);
    }

    /**
     * 司机处理推送请求
     */
    public function orderPushHandel($params)
    {
        $p_id = $params['p_id'];
        $type = $params['type'];
        //修改推送表状态
        $push = OrderPushT::get($p_id);
        $push->state = $type;
        $push->save();

        if ($type == OrderEnum::ORDER_PUSH_AGREE) {
            $this->prefixPushAgree($push->d_id);

        } else if ($type == OrderEnum::ORDER_PUSH_REFUSE) {
            $this->prefixPushRefuse($push->d_id);
        }

    }

    private function prefixPushAgree($d_id)
    {
        //更新order表状态 - 用触发器：update_order_state 解决

        /* $res = OrderT::update(['d_id' => $d_id, 'state' => OrderEnum::ORDER_ING], ['id' => $o_id]);
         if (!$res) {
             throw new UpdateException();
         }*/
        //更新司机状态:从正在派单移除；添加到已接单
        $redis = new \Redis();
        $redis->connect('127.0.0.1', 6379, 60);
        $redis->sRem('driver_order_ing', $d_id);
        $redis->sAdd('driver_order_receive', $d_id);

    }

    private function prefixPushRefuse($d_id)
    {
        //更新司机状态:从正在派单移除；添加到未接单
        $redis = new \Redis();
        $redis->connect('127.0.0.1', 6379, 60);
        $redis->sRem('driver_order_ing', $d_id);
        $redis->sAdd('driver_order_no', $d_id);
    }

    private function saveOrder($data)
    {
        $order = OrderT::create($data);
        if (!$order) {
            throw  new SaveException(['msg' => '下单失败']);
        }
        return $order->id;
    }

    private function saveOrderList($o_id)
    {
        $data = [
            'o_id' => $o_id,
            'state' => OrderEnum::ORDER_LIST_NO
        ];
        OrderListT::create($data);

    }

    private function findDriverToPush($order)
    {
        $redis = new \Redis();
        $redis->connect('127.0.0.1', 6379, 60);
        //查询所有司机并按距离排序
        $lat = $order['lat'];
        $lng = $order['lng'];
        $list = $redis->rawCommand('georadius', 'drivers_tongling', $lng, $lat, '1000000', 'km', 'ASC');
        if (!count($list)) {
            return false;
        }

        //设置三个set: 司机未接单；司机正在派单；司机已经接单
        foreach ($list as $k => $v) {
            $d_id = $v;
            if (Gateway::isUidOnline($d_id) &&
                $redis->sIsMember('driver_order_no', $d_id)) {
                //将司机从'未接单'移除，添加到：正在派单
                $redis->sRem('driver_order_no', $d_id);
                $redis->sAdd('driver_order_ing', $d_id);

                $push = OrderPushT::create(
                    [
                        'd_id' => $d_id,
                        'o_id' => $order->id,
                        'state' => OrderEnum::ORDER_PUSH_NO
                    ]
                );
                //通过websocket推送给司机
                $push_data = [
                    'type' => 'order',
                    'order_info' => [
                        'phone' => $order->phone,
                        'start' => $order->start,
                        'end' => $order->end,
                        'create_time' => $order->create_time,
                        'p_id' => $push->id,

                    ]
                ];
                (new GatewayService())->sendToClient($d_id, $push_data);
                //通过短信推送给司机
                $driver = DriverT::where('id', $d_id)->find();
                $phone = $driver->phone;
                (new SendSMSService())->sendOrderSMS($phone, ['code' => '*****' . substr($order->order_num, 5), 'order_time' => date('H:i', strtotime($order->create_time))]);
                break;
            }

        }
    }

}