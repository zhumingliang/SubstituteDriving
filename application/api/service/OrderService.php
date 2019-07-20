<?php


namespace app\api\service;


use app\api\model\DriverT;
use app\api\model\FarStateT;
use app\api\model\LocationT;
use app\api\model\LogT;
use app\api\model\OrderListT;
use app\api\model\OrderMoneyT;
use app\api\model\OrderPushT;
use app\api\model\OrderT;
use app\api\model\OrderV;
use app\api\model\StartPriceT;
use app\api\model\SystemOrderChargeT;
use app\api\model\TicketT;
use app\api\model\TimeIntervalT;
use app\api\model\WaitPriceT;
use app\api\model\WeatherT;
use app\lib\enum\CommonEnum;
use app\lib\enum\OrderEnum;
use app\lib\exception\SaveException;
use app\lib\exception\UpdateException;
use think\Db;
use think\db\Where;
use think\Exception;
use zml\tp_tools\CalculateUtil;

class OrderService
{
    /**
     * 小程序下单
     */
    public function saveMiniOrder($params)
    {
        try {
            $far = $this->prefixFar($params);
            $params['u_id'] = Token::getCurrentUid();
            $params['name'] = '先生/女士';
            $params['phone'] = Token::getCurrentTokenVar('phone');
            $params['from'] = OrderEnum::FROM_MINI;
            $params['type'] = OrderEnum::NOT_FIXED_MONEY;
            $params['state'] = OrderEnum::ORDER_NO;
            $params['order_num'] = time();
            $params['far_distance'] = $far['far_distance'];
            $params['far_money'] = $far['far_money'];
            $order = $this->saveOrder($params);
            $this->saveOrderList($order->id, OrderEnum::ORDER_LIST_NO);
            return $order->id;
        } catch (Exception $e) {
            LogT::create(['msg' => 'save_order_mini:' . $e->getMessage()]);
        }
    }

    /**
     * 司机自主下单
     */
    public function saveDriverOrder($params)
    {
        try {
            $d_id = Token::getCurrentUid();
            if ((new DriverService())->checkNoCompleteOrder($d_id)) {
                throw  new SaveException(['msg' => '创建订单失败,已有未完成的订单']);
            }
            if (key_exists('phone', $params) && strlen($params['phone'])) {
                $params['u_id'] = (new UserInfo('', ''))->getUserByPhone($params['phone']);
            }
            if (key_exists('name', $params) && !strlen($params['name'])) {
                $params['name'] = '先生/女士';
            }
            $params['d_id'] = $d_id;
            $params['from'] = OrderEnum::FROM_DRIVER;
            $params['type'] = OrderEnum::NOT_FIXED_MONEY;
            $params['state'] = OrderEnum::ORDER_ING;
            $params['order_num'] = time();
            $order = $this->saveOrder($params);
            $o_id = $order->id;
            //新增到订单待处理队列
            $this->saveOrderList($o_id, OrderEnum::ORDER_LIST_COMPLETE);

            //处理司机状态
            //未接单状态->已接单状态
            (new DriverService())->handelDriveStateByReceive($d_id);
            return $o_id;
        } catch (Exception $e) {
            LogT::create(['msg' => 'save_order_driver:' . $e->getMessage()]);
        }
    }

    /**
     * 管理员自主下单
     */
    public function saveManagerOrder($params)
    {
        try {
            $d_id = $params['d_id'];
            if (!(new DriverService())->checkDriverOrderNo($d_id)) {
                throw new SaveException(['msg' => '该司机已有订单，不能重复接单']);
            }
            if (key_exists('phone', $params) && strlen($params['phone'])) {
                $params['u_id'] = (new UserInfo('', ''))->getUserByPhone($params['phone']);
            }
            if (key_exists('name', $params) && !strlen($params['name'])) {
                $params['name'] = '先生/女士';
            }
            $params['from'] = OrderEnum::FROM_MANAGER;
            $params['state'] = OrderEnum::ORDER_NO;
            $params['order_num'] = time();
            $order = $this->saveOrder($params);
            $o_id = $order->id;
            //新增到订单待处理队列-状态：正在派单
            $this->saveOrderList($o_id, OrderEnum::ORDER_LIST_ING);

            //处理司机状态
            //未接单状态->已接单状态
            (new DriverService())->handelDriveStateByING($d_id);

            //推送给司机
            $this->pushToDriver($d_id, $order);
            return $o_id;
        } catch (Exception $e) {
            LogT::create(['msg' => 'save_order__manager:' . $e->getMessage()]);
        }
    }

    /**
     * 向司机推送服务-websocket/短信
     */
    private function pushToDriver($d_id, $order)
    {
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
        GatewayService::sendToDriverClient($d_id, $push_data);
        //通过短信推送给司机
        $driver = DriverT::where('id', $d_id)->find();
        $phone = $driver->phone;
        (new SendSMSService())->sendOrderSMS($phone, ['code' => '*****' . substr($order->order_num, 5), 'order_time' => date('H:i', strtotime($order->create_time))]);
    }

    private function prefixFar($params)
    {
        //计算距离
        $far_distance = CalculateUtil::GetDistance($params['start_lng'],
            $params['start_lat'], $params['end_lng'],
            $params['end_lat']);

        //检查远程接驾是否开启
        $far_state = FarStateT::find();
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
                if ($distance == 0) {
                    return $price;
                }
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

    public function getStartPrice($price)
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
     * 处理等待推送队列-定时服务
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
     * 处理推送列表-定时服务
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
        $push_type = $push->type;

        if ($type == OrderEnum::ORDER_PUSH_AGREE) {
            if ($push_type == "normal") {
                $this->prefixPushAgree($push->d_id);
                $this->sendToMini($push);
            } else if ($push_type == "transfer") {
                //释放转单司机
                $this->prefixPushRefuse($push->f_d_id);
                //处理原订单状态
                //由触发器解决
            }


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

    private function sendToMini($push)
    {
        $order = $this->getOrder($push->o_id);
        $u_id = $order->u_id;
        $d_id = $order->d_id;

        if ($u_id) {
            $send_data = [
                'type' => 'order',
                'data' => [
                    'id' => $order->id,
                    'driver_name' => $order->driver->username,
                    'driver_phone' => $order->driver->phone,
                    'distance' => $this->getDriverDistance($order->start_lng, $order->start_lat, $d_id)
                ]
            ];
            GatewayService::sendToMiniClient($u_id, json_encode($send_data));
        }

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
        return $order;
    }

    private function saveOrderList($o_id, $state)
    {
        $data = [
            'o_id' => $o_id,
            'state' => $state
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

            if (GatewayService::isDriverUidOnline($d_id) &&
                $redis->sIsMember('driver_order_no', $d_id)) {
                //将司机从'未接单'移除，添加到：正在派单
                $redis->sRem('driver_order_no', $d_id);
                $redis->sAdd('driver_order_ing', $d_id);

                $push = OrderPushT::create(
                    [
                        'd_id' => $d_id,
                        'o_id' => $order->id,
                        'type' => 'normal',
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
                GatewayService::sendToDriverClient($d_id, $push_data);
                //通过短信推送给司机
                $driver = DriverT::where('id', $d_id)->find();
                $phone = $driver->phone;
                (new SendSMSService())->sendOrderSMS($phone, ['code' => '*****' . substr($order->order_num, 5), 'order_time' => date('H:i', strtotime($order->create_time))]);
                break;
            }

        }
    }

    public function orderCancel($params)
    {
        $order = $this->getOrder($params['id']);
        //检查订单是否可以取消
        if ($order->state != OrderEnum::ORDER_NO || $order->begin != CommonEnum::STATE_IS_FAIL) {
            throw new UpdateException(['msg' => '订单已经开始，不能撤销']);
        }

        $grade = Token::getCurrentTokenVar('type');
        $order->state = OrderEnum::ORDER_CANCEL;
        $order->cancel_remark = $params['remark'];
        $order->cancel_type = $grade;
        $res = $order->save();
        if ($grade == 'manager') {
            //管理员撤单时已经回滚司机和订单状态
            return true;
        }
        //处理司机状态
        (new DriverService())->handelDriveStateByCancel($params['id']);

        //处理订单状态
        //由接单中/派单中/未接单->订单撤销
        OrderListT::update(['state' => OrderEnum::ORDER_LIST_CANCEL],
            ['o_id' => $params['id']]);

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

    /**
     * 到达起点
     */
    public function arrivingStart($id)
    {
        $order = $this->getOrder($id);
        $order->arriving_time = date('Y-m-d H:i:s', time());
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
            $info = $this->prepareOrderInfo($order);
        } else {
            $driver_location = $this->getDriverLocation($order->d_id);
            $info = [
                'state' => $order->state,
                'driver' => $order->driver->username,
                'phone' => $order->driver->phone,
                'start' => $order->start,
                'end' => $order->end,
                'begin' => $order->begin,
                'arriving_time' => $order->arriving_time,
                'receive_time' => $order->receive_time,
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
                return $this->prepareOrderInfo($order);
            }

            if ($order->type === 2) {
                $money = $order->money;
                $ticket_money = 0;
            } else {
                $distance_money = $params['distance_money'];
                $wait_money = $params['wait_money'];
                $distance = $params['distance'];
                //处理恶劣天气费用
                $weather_money = $this->prefixWeather($distance_money);

                //处理订单金额
                $money = $distance_money + $wait_money + $weather_money + $order->far_money;

                $ticket_money = 0;
                if ($order->ticket) {
                    $ticket_money = $order->ticket->money;
                    $money -= $ticket_money;
                    //处理优惠券
                    $t_res = TicketT::update(['state' => CommonEnum::STATE_IS_FAIL], ['id' => $order->ticket->id]);
                    if (!$t_res) {
                        Db::rollback();
                        throw new SaveException(['msg' => '保存处理优惠券失败']);
                    }
                }
                $order->distance = $distance;
                $order->distance_money = $distance_money;
                $order->ticket_money = $ticket_money;
                $order->wait_time = $wait_time;
                $order->wait_money = $wait_money;
                $order->weather_money = $weather_money;
                $order->money = $money;
            }

            /*  //处理 订单距离/距离产生的价格
              $redis = new Redis();
              $distance = $redis->zScore('order:distance', $id);
              $startRule = StartPriceT::where('type', 1)
                  ->where('state', CommonEnum::STATE_IS_OK)
                  ->order('order')
                  ->select();
              $distance_money = $this->prefixStartPriceWithDistance($distance, $startRule, 'start');

              //处理等待费用
              $wait_money = $this->prefixWait($wait_time);*/
            $order->state = OrderEnum::ORDER_COMPLETE;
            $res = $order->save();
            if (!$res) {
                Db::rollback();
                throw new SaveException(['msg' => '保存结算数据失败']);
            }
            //处理抽成
            if (!$this->prefixOrderCharge($id, $order->d_id, $money, $ticket_money)) {
                Db::rollback();
                throw new SaveException(['msg' => '订单抽成失败']);
            }
            Db::commit();
            (new DriverService())->handelDriveStateByComplete($id);
            return $this->prepareOrderInfo($order);

        } catch (Exception $e) {
            Db::rollback();
            throw $e;
        }

    }

    public function prefixOrderCharge($o_id, $d_id, $money, $ticket_money)
    {

        $orderCharge = SystemOrderChargeT::find();
        $insurance = $orderCharge->insurance;
        $order = $orderCharge->order;
        $order_money = ($money + $ticket_money) * $order;
        $data = [
            [
                'o_id' => $o_id,
                'd_id' => $d_id,
                'money' => $insurance,
                'type' => 1,

            ], [
                'o_id' => $o_id,
                'd_id' => $d_id,
                'money' => $order_money,
                'type' => 2,
            ]
        ];
        $res = (new OrderMoneyT())->saveAll($data);
        return $res;

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

    private function prepareOrderInfo($order)
    {

        if ($order->state == OrderEnum::ORDER_COMPLETE) {
            $info = [
                'driver_name' => $order->driver->username,
                'driver_phone' => $order->driver->phone,
                'start' => $order->start,
                'end' => $order->end,
                'from' => $order->from,
                'name' => $order->name,
                'phone' => $order->phone,
                'create_time' => $order->create_time,
                'state' => $order->state,
                'distance' => $order->distance,
                'distance_money' => $order->distance_money,
                'money' => $order->money,
                'far_distance' => $order->far_distance,
                'far_money' => $order->far_money,
                'ticket_money' => $order->ticket_money,
                'wait_time' => $order->wait_time,
                'wait_money' => $order->wait_money,
                'weather_money' => $order->weather_money,

            ];
        } else {
            $info = [
                'driver_name' => $order->driver->username,
                'driver_phone' => $order->driver->phone,
                'start' => $order->start,
                'end' => $order->end,
                'from' => $order->from,
                'name' => $order->name,
                'phone' => $order->phone,
                'create_time' => $order->create_time,
                'state' => $order->state
            ];
        }

        return $info;

    }


    public function getDriverLocation($u_id)
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

    private function getDriverDistance($user_lng, $user_lat, $d_id)
    {
        $location = $this->getDriverLocation($d_id);
        if ($location['lng'] && $location['lat']) {
            return CalculateUtil::GetDistance($user_lat, $user_lng, $location['lat'], $location['lng']);

        }
        return 0;

    }

    private function getOrder($id)
    {
        $order = OrderT::with(['ticket', 'driver'])->get($id);
        if (!$order) {
            throw new UpdateException(['msg' => '订单不存在']);
        }
        $grade = Token::getCurrentTokenVar('type');
        if ($grade == 'manager') {
            return $order;
        } else {
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

    public function transferOrder($params)
    {
        //检查订单是否开始
        $order = $this->getOrder($params['id']);
        if ($order->state != OrderEnum::ORDER_NO) {
            throw  new SaveException(['msg' => '订单已开始，不能转单']);
        }
        $d_id = $params['id'];

        //检查新司机状态是否有订单，修改司机状态
        if (!$this->updateDriverCanReceive($d_id)) {
            throw  new SaveException(['msg' => '该司机有订单派送中，暂时不能接单']);
        }

        //计算距离和价格
        $distance_info = $this->getDistanceInfoToPush($order);
        //新增推送状态
        $this->pushToDriverWithTransfer($d_id, $order, $distance_info);

    }

    private function updateDriverCanReceive($d_id)
    {
        //检查新司机状态是否有订单，修改司机状态
        $redis = new \Redis();
        $redis->connect('127.0.0.1', 6379, 60);
        if (!$redis->sIsMember('driver_order_no', $d_id)) {
            return false;
        }
        //将被转单司机从'未接单'移除，添加到：正在派单
        $redis->sRem('driver_order_no', $d_id);
        $redis->sAdd('driver_order_ing', $d_id);
    }

    /**
     * 向司机推送服务-转单服务-websocket/短信
     */
    private function pushToDriverWithTransfer($d_id, $order, $distance_info, $push_type = "transfer")
    {

        $from_name = '';
        if ($push_type == "transfer") {
            $from_name = $order->driver->username;

        } else if ($push_type == "manager") {
            $from_name = "管理员";
        }
        $push = OrderPushT::create(
            [
                'f_d_id' => $order->d_id,
                'd_id' => $d_id,
                'o_id' => $order->id,
                'type' => 'transfer',
                'state' => OrderEnum::ORDER_PUSH_NO
            ]
        );
        //通过websocket推送给司机
        $push_data = [
            'type' => $push_type,
            'order_info' => [
                'from' => $from_name,
                'name' => $order->name,
                'phone' => $order->phone,
                'start' => $order->start,
                'end' => $order->end,
                'create_time' => $order->create_time,
                'p_id' => $push->id,
                'distance' => $distance_info['distance'],
                'distance_money' => $distance_info['distance_money']
            ]
        ];
        GatewayService::sendToDriverClient($d_id, $push_data);
        //通过短信推送给司机
        $driver = DriverT::where('id', $d_id)->find();
        $phone = $driver->phone;
        (new SendSMSService())->sendOrderSMS($phone, ['code' => '*****' . substr($order->order_num, 5), 'order_time' => date('H:i', strtotime($order->create_time))]);
    }

    public function choiceDriverByManager($params)
    {
        //检测被推送司机状态
        if (!(new DriverService())->checkDriverOrderNo($params['d_id'])) {
            throw new SaveException(['msg' => '司机已有订单，不能接单']);
        }

        //清除订单信息
        //1.清除司机信息
        $o_id = $params['o_id'];
        $push = OrderPushT::where('o_id', $o_id)
            ->order('create_time')
            ->find();
        if ($push) {
            $o_d_id = $push->d_id;
            (new DriverService())->handelDriveStateByCancel($o_d_id);
        }
        //2.删除推送
        $push->delete();

        //推送给司机
        $d_id = $params['d_id'];
        $order = $this->getOrder($o_id);
        $distance_info = $this->getDistanceInfoToPush($order);
        $this->pushToDriverWithTransfer($d_id, $order, $distance_info);

    }

    private function getDistanceInfoToPush($order)
    {
        $distance = 0;
        $start_lng = $order->start_lng;
        $start_lat = $order->start_lat;
        $end_lng = $order->end_lng;
        $end_lat = $order->end_lat;
        if (strlen($end_lng) && strlen($end_lat)) {
            $distance = CalculateUtil::GetDistance($start_lat, $start_lng, $end_lat, $end_lng);
        }
        $startRule = StartPriceT::where('type', 1)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->order('order')
            ->select();
        $distance_money = $this->prefixStartPriceWithDistance($distance, $startRule, 'start');

        return [
            'distance' => $distance,
            'distance_money' => $distance_money
        ];
    }

    public function driverOrders($page, $size, $time_begin, $time_end)
    {
        $d_id = Token::getCurrentUid();
        $orders = OrderT::getDriverOrders($d_id, $page, $size, $time_begin, $time_end);
        $orders['data'] = $this->prefixTransferInfo($orders['data']);
        $orders['statistic'] = $this->getDriverOrdersStatistic($d_id, $time_begin, $time_end);
        return $orders;

    }

    public function orderInfo($id)
    {
        $order = $this->getOrder($id);
        $info = $this->prepareOrderInfo($order);
        return $info;
    }

    public function managerOrders($page, $size, $driver, $time_begin, $time_end)
    {
        $orders = OrderV::managerOrders($page, $size, $driver, $time_begin, $time_end);
        $orders['data'] = $this->prefixTransferInfo($orders['data']);
        $orders['statistic'] = $this->getManagerOrdersStatistic($driver, $time_begin, $time_end);
        return $orders;


    }

    private function getDriverOrdersStatistic($d_id, $time_begin, $time_end)
    {
        $ordersMoney = OrderT::driverOrdersMoney($d_id, $time_begin, $time_end);
        return [
            'orders_count' => OrderT::driverOrderCount($d_id, $time_begin, $time_end),
            'all_money' => $ordersMoney['all_money'],
            'ticket_money' => $ordersMoney['ticket_money'],
        ];

    }


    private function getManagerOrdersStatistic($driver, $time_begin, $time_end)
    {
        $ordersMoney = OrderV::ordersMoney($driver, $time_begin, $time_end);
        return [
            'members' => OrderV::members($driver, $time_begin, $time_end),
            'orders_count' => OrderV::orderCount($driver, $time_begin, $time_end),
            'all_money' => $ordersMoney['all_money'],
            'ticket_money' => $ordersMoney['ticket_money'],
        ];

    }

    private function prefixTransferInfo($data)
    {
        if (!count($data)) {
            return $data;
        }

        foreach ($data as $k => $v) {

            if ($v['superior_id']) {
                $data[$k]['transfer'] = 1;
                $data[$k]['superior'] = DriverT::field('username')
                    ->where('id', $v['superior_id'])->find()
                    ->toArray();
            }
            unset($data[$k]['superior_id']);
        }
        return $data;

    }

    public function recordsOfConsumption($page, $size, $phone)
    {
        $list = OrderT::recordsOfConsumption($page, $size, $phone);
        $list['statistic'] = $this->recordsOfConsumptionStatistic($phone);
        return $list;
    }

    private function recordsOfConsumptionStatistic($phone)
    {
        return [
            'count' => OrderT::ConsumptionCount($phone),
            'money' => OrderT::ConsumptionMoney($phone),

        ];

    }

    public function orderLocations($id)
    {
        $locations = LocationT::where('o_id', $id)
            ->where('begin', CommonEnum::STATE_IS_OK)
            ->field('lat,lng')
            ->select();
        return $locations;
    }

    public function current($page, $size)
    {
        $orders = OrderT::currentOrders($page, $size);
        return $orders;
    }

    /**
     * 撤回订单
     */
    public function withdraw($o_id)
    {

        $order = $this->getOrder($o_id);

        //1.检测订单是否被接单
        //2.修改司机状态
        //3.修改订单状态
        if ($order->begin == CommonEnum::STATE_IS_OK) {
            throw new UpdateException(['msg' => '订单已开始出发，不能被撤回']);
        }
        if ($order->d_id) {
            (new DriverService())->handelDriveStateByCancel($order->d_id);
        }
        //修改推送表状态
        $push = OrderPushT::where('o_id', $o_id)
            ->order('create_time desc')
            ->find();
        if ($push) {
            //触发器修改订单状态
            //将订单派送列表中订单状态改为撤销状态
            $push->state = OrderEnum::ORDER_PUSH_WITHDRAW;
            $push->save();
        }


    }

    public function CMSManagerOrders($page, $size, $driver, $time_begin, $time_end, $order_state, $order_from)
    {
        $orders = OrderV::CMSManagerOrders($page, $size, $driver, $time_begin, $time_end, $order_state, $order_from);
        $orders['statistic'] = $this->getManagerOrdersStatistic($driver, $time_begin, $time_end);
        return $orders;


    }

    public function CMSInsuranceOrders($page, $size, $time_begin, $time_end)
    {
        $orders = OrderV::CMSInsuranceOrders($page, $size, $time_begin, $time_end);
        $orders['statistic'] = OrderV::orderCount('', $time_begin, $time_end);
        return $orders;


    }


}