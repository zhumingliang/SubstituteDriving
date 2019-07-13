<?php


namespace app\api\service;


use app\api\model\DriverT;
use app\api\model\FarStateT;
use app\api\model\OrderListT;
use app\api\model\OrderPushT;
use app\api\model\OrderT;
use app\api\model\StartPriceT;
use app\api\model\TicketT;
use app\api\model\TimeIntervalT;
use app\api\model\WaitPriceT;
use app\api\model\WeatherT;
use app\lib\enum\CommonEnum;
use app\lib\enum\OrderEnum;
use app\lib\exception\SaveException;
use app\lib\exception\UpdateException;
use GatewayClient\Gateway;
use http\Params;
use think\Db;
use think\Exception;
use zml\tp_tools\CalculateUtil;
use zml\tp_tools\Redis;

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

    /**
     * 司机自主下单
     */
    public function saveDriverOrder($params)
    {
        $d_id = Token::getCurrentUid();
        if ((new DriverService())->checkNoCompleteOrder($d_id)) {
            throw  new SaveException(['msg' => '创建订单失败,已有未完成的订单']);
        }
        if (key_exists('phone', $params) && strlen($params['phone'])) {
            $params['u_id'] = (new UserInfo('', ''))->getUserByPhone($params['phone']);
        }
        $params['d_id'] = $d_id;
        $params['from'] = OrderEnum::FROM_DRIVER;
        $params['type'] = OrderEnum::NOT_FIXED_MONEY;
        $params['state'] = OrderEnum::ORDER_ING;
        $params['order_num'] = time();
        $o_id = $this->saveOrder($params);

        //处理司机状态
        //未接单状态->已接单状态
        (new DriverService())->handelDriveStateByING($d_id);
        return $o_id;
    }


    private function prefixFar($params)
    {
        //计算距离
        $far_distance = CalculateUtil::GetDistance($params['start_lng'],
            $params['start_lat'], $params['end_lng'],
            $params['end_lat']);

        //检查远程接驾是否开启
        $far_state = FarStateT::get();
        if ($far_state->open == 2) {
            return [
                'far_money' => 0,
                'far_distance' => $far_distance,
            ];
        }

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

    private function prefixStartPriceWithDistance($distance, $Rule, $type = 'far')
    {
        $money_new = 0;
        $count = count($Rule) - 1;
        foreach ($Rule as $k => $v) {
            $price = $v['price'];
            if ($k == 0 && $type == 'start') {
                $price = $this->getStartPrice($price);
            }

            if ($distance <= 0) {
                return $money_new;
                break;
            }
            if ($count > $k) {
                $money_new += $price;
                $distance -= $v['distance'];
            } else {
                $money_new += $v['price'] * ceil($distance / $v['distance']);
            }

        }
        return $money_new;

    }

    private function getStartPrice($price)
    {
        $time_now = date('H:i', time());
        $interval = TimeIntervalT::where('state', CommonEnum::STATE_IS_OK)
            ->whereTime('time_begin', '<=', $time_now)
            ->whereTime('time_end', '>=', $time_now)
            ->find();
        if (!$interval) {
            return $price;
        }
        return $interval->price;

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

        //设置三个set: 司机未接单 driver_order_no；司机正在派单 driver_order_ing；司机已经接单 driver_order_receive
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

    public function miniCancel($params)
    {
        $order = $this->getOrder($params['id']);
        $order->state = OrderEnum::ORDER_CANCEL;
        $order->cancel_remark = $params['remark'];
        $res = $order->save();
        if (!$res) {
            throw new UpdateException();
        }
    }

    public function orderBegin($params)
    {
        $order = $this->getOrder($params['id']);
        $order->begin = CommonEnum::STATE_IS_OK;
        $res = $order->save();
        if (!$res) {
            throw new UpdateException();
        }
    }

    public function miniOrders($page, $size)
    {
        $u_id = Token::getCurrentUid();
        $orders = OrderT::miniOrders($u_id, $page, $size);
        return $orders;

    }

    public function miniOrder($id)
    {
        $order = $this->getOrder($id);
        if ($order->state == OrderEnum::ORDER_NO) {
            $info = [
                'state' => OrderEnum::ORDER_NO
            ];

        } else if ($order->state == OrderEnum::ORDER_CANCEL) {
            $info = [
                'state' => OrderEnum::ORDER_CANCEL
            ];

        } else if ($order->state == OrderEnum::ORDER_COMPLETE) {
            $info = $this->prepareCompleteInfo($order);
        } else {
            $driver_location = $this->getDriverLocation($order->d_id);
            $info = [
                'driver' => $order->driver->username,
                'phone' => $order->driver->phone,
                'start' => $order->start,
                'begin' => $order->begin,
                'driver_lng' => $driver_location['lng'],
                'driver_lat' => $driver_location['lat']
            ];

        }

        return $info;
    }

    public function driverCompleteOrder($params)
    {

        try {
            Db::startTrans();

            $id = $params['id'];
            $wait_time = $params['wait_time'];
            $order = $this->getOrder($id);
            if ($order->state == OrderEnum::ORDER_COMPLETE) {
                return $this->prepareCompleteInfo($order);
            }

            //处理 订单距离/距离产生的价格
            $redis = new Redis();
            $distance = $redis->zScore('order:distance', $id);
            $startRule = StartPriceT::where('type', 1)
                ->where('state', CommonEnum::STATE_IS_OK)
                ->order('order')
                ->select();
            $distance_money = $this->prefixStartPriceWithDistance($distance, $startRule, 'start');

            //处理等待费用
            $wait_money = $this->prefixWait($wait_time);

            //处理恶劣天气费用
            $weather_money = $this->prefixWeather($distance_money);

            //处理订单金额
            $money = $distance_money + $wait_money + $weather_money + $order->far_money;

            if ($order->ticket) {
                $ticket = $order->ticket;
                $money -= $ticket->money;
                //处理优惠券
                $t_res = TicketT::update(['state' => CommonEnum::STATE_IS_FAIL], ['id' => $ticket->id]);
                if (!$t_res) {
                    Db::rollback();
                    throw new SaveException(['msg' => '保存处理优惠券失败']);
                }
            }

            $order->state = OrderEnum::ORDER_COMPLETE;
            $order->distance = $distance;
            $order->distance_money = $distance_money;
            $order->wait_time = $wait_time;
            $order->wait_money = $wait_money;
            $order->weather_money = $weather_money;
            $order->money = $money;
            $res = $order->save();
            if (!$res) {
                Db::rollback();
                throw new SaveException(['msg' => '保存结算数据失败']);
            }
            Db::commit();
            (new DriverService())->handelDriveStateByComplete($id);
            return $this->prepareCompleteInfo($order);

        } catch (Exception $e) {
            Db::rollback();
            throw $e;
        }

    }

    private function prefixWait($wait_time)
    {
        $wait = WaitPriceT::where('state', CommonEnum::STATE_IS_OK)
            ->find();
        if (!$wait || ($wait->free >= $wait_time)) {
            return 0;
        }

        return (ceil($wait_time / 60) - $wait->free) * $wait->price;


    }

    private function prefixWeather($distance_money)
    {
        $weather = WeatherT::find();
        if (!$weather || $weather->state == CommonEnum::STATE_IS_FAIL) {
            return 0;
        }

        return ceil($distance_money * ($weather->ratio - 1));

    }

    private function prepareCompleteInfo($order)
    {

        $info = [
            'state' => OrderEnum::ORDER_COMPLETE,
            'distance' => $order->distance,
            'distance_money' => $order->distance_money,
            'money' => $order->money,
            'far_distance' => $order->far_distance,
            'far_money' => $order->far_money,
            'ticket_money' => $order->ticket ? $order->ticket->money : 0,
            'wait_time' => $order->wait_time,
            'wait_money' => $order->wait_money,
            'weather_money' => $order->weather_money,

        ];
        return $info;

    }

    private function getDriverLocation($u_id)
    {
        $redis = new \Redis();
        $redis->connect('127.0.0.1', 6379, 60);
        $location = $redis->rawCommand('geopos', 'drivers_tongling', $u_id);
        if ($location) {
            $lng = $location[0][0];
            $lat = $location[0][1];
        } else {
            $lng = null;
            $lat = null;
        }

        return [
            'lng' => $lng,
            'lat' => $lat,
        ];
    }

    private function getOrder($id)
    {
        $order = OrderT::with(['ticket', 'driver'])->get($id);
        if (!$order) {
            throw new UpdateException(['msg' => '订单不存在']);
        }
        $grade = Token::getCurrentTokenVar('type');
        if ($grade == 'driver') {
            $field_id = $order->d_id;
        } else {
            $field_id = $order->u_id;
        }
        if (Token::getCurrentUid() != $field_id) {
            throw new UpdateException(['msg' => '无权限操作此订单']);
        }
        return $order;
    }


}