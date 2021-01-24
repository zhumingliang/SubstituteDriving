<?php


namespace app\api\service;


use app\api\model\DriverT;
use app\api\model\FarStateT;
use app\api\model\LocationT;
use app\api\model\LogT;
use app\api\model\MiniPushT;
use app\api\model\OrderListT;
use app\api\model\OrderMoneyT;
use app\api\model\OrderMsgT;
use app\api\model\OrderPushT;
use app\api\model\OrderRevokeT;
use app\api\model\OrderT;
use app\api\model\OrderV;
use app\api\model\StartPriceT;
use app\api\model\SystemOrderChargeT;
use app\api\model\TimeIntervalT;
use app\api\model\WaitPriceT;
use app\api\model\WeatherT;
use app\lib\enum\CommonEnum;
use app\lib\enum\OrderEnum;
use app\lib\enum\TicketEnum;
use app\lib\exception\AuthException;
use app\lib\exception\ParameterException;
use app\lib\exception\SaveException;
use app\lib\exception\UpdateException;
use GuzzleHttp\Handler\CurlHandler;
use think\Db;
use think\Exception;
use think\facade\Log;
use zml\tp_tools\CalculateUtil;
use zml\tp_tools\Redis;

class OrderService
{
    /**
     * 小程序下单
     */
    public function saveMiniOrder($params)
    {
        try {
            //  $far = $this->prefixFar($params);
            $params['u_id'] = Token::getCurrentUid();
            $params['name'] = Token::getCurrentTokenVar('nickName');
            $params['phone'] = Token::getCurrentTokenVar('phone');
            $params['order_lat'] = empty($params['start_lat']) ? '' : $params['start_lat'];
            $params['order_lng'] = empty($params['start_lng']) ? '' : $params['start_lng'];
            $params['order_address'] = empty($params['start']) ? '' : $params['start'];
            $params['from'] = OrderEnum::FROM_MINI;
            $params['type'] = OrderEnum::NOT_FIXED_MONEY;
            $params['state'] = OrderEnum::ORDER_NO;
            $params['order_num'] = $this->getOrderNumber();
            $params['company_id'] = Token::getCurrentTokenVar('company_id');

            $this->sendMiniMsgToManager($params['company_id'], $params['order_num']);
            return $this->createOrderWithoutDriver($params);
        } catch (Exception $e) {
            LogT::create(['msg' => 'save_order_mini:' . $e->getMessage()]);
            throw  $e;
        }
    }

    private function sendMiniMsgToManager($company_id, $orderNum)
    {
        $config = OrderMsgT::where('company_id', $company_id)->find();
        if ($config) {
            $phones = $config->phone;
            $phoneArr = explode(',', $phones);
            if (count($phoneArr)) {
                foreach ($phoneArr as $k => $v) {
                    (new SendSMSService())->sendOrderSMS($v,
                        ['code' => 'OK' . $orderNum, 'order_time' => date('Y-m-d H:i:s')]);
                }
            }
        }

    }

    /**
     * 司机自主下单
     */
    public function saveDriverOrder($params)
    {
        try {
            $d_id = Token::getCurrentUid();
            (new UserService())->checkDriverState($d_id);
            if ((new DriverService())->checkNoCompleteOrder($d_id)) {
                throw new SaveException(['msg' => '创建订单失败,已有未完成的订单']);
            }
            if (!empty($params['phone'])) {
                $params['u_id'] = (new UserInfo('', ''))->checkUserByPhone($params['phone'], $params['name'], 3, Token::getCurrentTokenVar('username'));
            }
            if (empty($params['name'])) {
                $params['name'] = '先生/女士';
            }
            $company_id = (new DriverService())->getDriverCompanyId($d_id);
            $params['company_id'] = $company_id;
            $params['d_id'] = $d_id;
            $params['from'] = OrderEnum::FROM_DRIVER;
            $params['type'] = OrderEnum::NOT_FIXED_MONEY;
            $params['state'] = OrderEnum::ORDER_ING;
            $params['order_num'] = $this->getOrderNumber();
            $params['order_lat'] = empty($params['start_lat']) ? '' : $params['start_lat'];
            $params['order_lng'] = empty($params['start_lng']) ? '' : $params['start_lng'];
            $params['order_address'] = empty($params['start']) ? '' : $params['start'];
            if (!empty($params['t_id'])) {
                (new TicketService())->prefixTicketHandel($params['t_id'], TicketEnum::STATE_ING);
            }
            $order = $this->saveOrder($params);
            $o_id = $order->id;
            //新增到订单待处理队列
            $this->saveOrderList($o_id, OrderEnum::ORDER_LIST_COMPLETE);

            //处理司机状态
            //未接单状态->已接单状态
            (new DriverService())->handelDriveStateByReceive($d_id);
            //发送短信
            (new SendSMSService())->sendDriveCreateOrderSMS($params['phone'], []);
            return $o_id;
        } catch (Exception $e) {
            throw  $e;
        }
    }

    /**
     * 管理员自主下单
     */
    public function saveManagerOrder($params)
    {
        try {
            if (key_exists('phone', $params) && strlen($params['phone'])) {
                $params['u_id'] = (new UserInfo('', ''))->checkUserByPhone($params['phone'], $params['name'], 4, "管理员");
            }
            if (key_exists('name', $params) && !strlen($params['name'])) {
                $params['name'] = '先生/女士';
            }
            $params['company_id'] = Token::getCurrentTokenVar('company_id');
            $params['from'] = OrderEnum::FROM_MANAGER;
            $params['state'] = OrderEnum::ORDER_NO;
            $params['order_num'] = $this->getOrderNumber();
            $params['order_lat'] = empty($params['start_lat']) ? '' : $params['start_lat'];
            $params['order_lng'] = empty($params['start_lng']) ? '' : $params['start_lng'];
            $params['order_address'] = empty($params['start']) ? '' : $params['start'];

            if (!empty($params['t_id'])) {
                (new TicketService())->prefixTicketHandel($params['t_id'], TicketEnum::STATE_ING);
            }
            //处理无司机订单
            if (empty($params['d_id'])) {
                return $this->createOrderWithoutDriver($params);
            }

            $d_id = $params['d_id'];
            if (!(new DriverService())->checkDriverOrderNo($d_id)) {
                throw new SaveException(['msg' => '该司机已有订单，不能重复接单']);
            }
            unset($params['d_id']);
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
            LogT::create(['msg' => 'save_order_manager:' . $e->getMessage()]);
            throw  $e;
        }
    }

    private function createOrderWithoutDriver($params)
    {
        $order = $this->saveOrder($params);
        $this->saveOrderList($order->id, OrderEnum::ORDER_LIST_NO);
        if (!empty($params['t_id'])) {
            (new TicketService())->prefixTicketHandel($params['t_id'], TicketEnum::STATE_ING);
        }
        return $order->id;
    }

    /**
     * 向司机推送服务-websocket/短信
     */
    private function pushToDriver($d_id, $order)
    {
        try {
            //通过短信推送给司机
            $driver = DriverT::where('id', $d_id)->find();
            $phone = $driver->phone;
            (new SendSMSService())->sendOrderSMS($phone, ['code' => 'OK' . $order->order_num, 'order_time' => date('H:i', strtotime($order->create_time))]);

            $distance_info = $this->getDistanceInfoToPush($order, $d_id);
            //通过websocket推送给司机
            $push_data = [
                'type' => 'order',
                'o_id' => $order->id,
                'd_id' => $d_id,
                'name' => $order->name,
                'company_id' => $order->company_id,
                'from' => "管理员建单",
                'phone' => $order->phone,
                'start' => $order->start,
                'end' => $order->end,
                'distance' => $distance_info['distance'],
                'distance_money' => 0,
                'create_time' => $order->create_time,
                'limit_time' => time(),
                'p_id' => $this->savePushCode($order->id, $d_id, 'normal')
            ];
            (new TaskService())->sendToDriverTask($push_data);
        } catch (Exception $e) {
            LogService::save('error:' . $e->getMessage()
            );
        }

    }

    public function savePushCode($order_id, $driver_id, $type = "normal", $f_d_id = 0)
    {
        $hashKey = $order_id;
        $data = [
            'order_id' => $order_id,
            'driver_id' => $driver_id,
            'type' => $type,
            'f_d_id' => $f_d_id,
            'state' => 1
        ];
        Redis::instance()->hMset($hashKey, $data);
        return $hashKey;
    }

    private function prefixFar($start_lng, $start_lat, $driver_lng, $driver_lat)
    {
        //计算距离
        $far_distance = CalculateUtil::GetDistance($start_lng, $start_lat, $driver_lng, $driver_lat);

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

    public function getStartPrice($company_id, $price, $id = 0)
    {
        $interval = TimeIntervalT::where('company_id', $company_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->select()->toArray();
        if (!$interval) {
            return $price;
        }
        $dateTime = time();
        if ($id) {
            $order = OrderT::get($id);
            if (!empty($order->begin_time)) {
                $dateTime = strtotime($order->begin_time);
            }
        }
        $day = date('Y-m-d', $dateTime);
        foreach ($interval as $k => $v) {
            $time_begin = strtotime($day . ' ' . $v['time_begin']);
            $time_end = strtotime($day . ' ' . $v['time_end']);
            if ($time_begin <= $dateTime && $dateTime <= $time_end) {
                $price = $v['price'];
                break;
            }
        }
        return $price;

    }

    /**
     * 处理等待推送队列-定时服务
     */
    /*    public function orderListHandel()
        {
            //查询待处理订单并将订单状态改为处理中
            $orderList = OrderListT::where('state', OrderEnum::ORDER_LIST_NO)
                ->order('create_time desc')
                // ->find();
                ->limit(0, 3)->select()->toArray();
            if (!$orderList) {
                return true;
            }

            foreach ($orderList as $k => $v) {
                OrderListT::update(['state' => OrderEnum::ORDER_LIST_ING], ['id' => $v['id']]);
            }


            $orderList->state = OrderEnum::ORDER_LIST_ING;
            $orderList->save();

            //获取订单信息并检测订单状态
            $order = OrderT::getOrder($orderList->o_id);
            if (!$order || $order->state != OrderEnum::ORDER_NO
                || $order->stop == OrderEnum::ORDER_STOP) {
                $orderList->state = OrderEnum::ORDER_LIST_COMPLETE;
                $orderList->save();
                return true;
            }
            //查找司机并推送
            if (!$this->findDriverToPush($order)) {
                $orderList->state = OrderEnum::ORDER_LIST_NO;
                $orderList->save();
            }

        }*/


    public function orderListHandel()
    {
        //查询待处理订单并将订单状态改为处理中
        $orderList = OrderListT::where('state', OrderEnum::ORDER_LIST_NO)
            ->order('create_time desc')
            ->limit(0, 1)->select()
            ->toArray();
        if (!$orderList) {
            return true;
        }

        foreach ($orderList as $k => $v) {
            OrderListT::update(['state' => OrderEnum::ORDER_LIST_ING],
                ['id' => $v['id']]);
        }

        foreach ($orderList as $k => $v) {
            $this->prefixOrderList($v['o_id'], $v['id']);
        }

    }

    private function prefixOrderList($o_id, $list_id)
    {
        try {
            //获取订单信息并检测订单状态
            $order = OrderT::getOrder($o_id);
            if (!$order || $order->state != OrderEnum::ORDER_NO
            ) {
                OrderListT::update(['state' => OrderEnum::ORDER_LIST_COMPLETE], ['id' => $list_id]);
                return true;
            }
            //查找司机并推送
            $push = $this->findDriverToPush($order);
            if ($push == CommonEnum::STATE_IS_FAIL) {
                OrderListT::update(['state' => OrderEnum::ORDER_LIST_NO], ['id' => $list_id]);
            }
        } catch (Exception $e) {
            LogService::save('prefixOrderList:' . $e->getMessage());
        }


    }


    /**
     * 处理推送列表-定时服务
     */
    public function handelDriverNoAnswer()
    {
        try {
            $push = OrderPushT::where('state', OrderEnum::ORDER_PUSH_NO)
                ->select()->toArray();
            if (count($push)) {
                foreach ($push as $k => $v) {
                    $d_id = $v['d_id'];
                    if (time() > $v['limit_time'] + config('setting.driver_push_expire_in')) {
                        $this->prefixPushRefuse($d_id);
                        OrderPushT::update(['state' => OrderEnum::ORDER_PUSH_INVALID], ['id' => $v['id']]);
                    } else {
                        $res = [
                            'd_id' => $d_id,
                            'receive' => $v['receive'],
                            'donline' => GatewayService::isDriverUidOnline($d_id),
                            'online' => (new DriverService())->checkOnline($d_id)
                        ];
                        if ($v['receive'] == 2 && !empty($v['message'])
                            && GatewayService::isDriverUidOnline($d_id)
                            && (new DriverService())->checkOnline($d_id)
                        ) {
                            GatewayService::sendToDriverClient($d_id,
                                json_decode($v['message'], true));
                        }
                    }
                }
            }
        } catch (Exception $e) {
            LogService::save('handelDriverNoAnswer:' . $e->getMessage());
        }


    }


    /**
     * 处理接单通知推送列表-定时服务
     */
    public function handelMiniNoAnswer()
    {
        try {
            $push = MiniPushT::where('state', '<>', 3)
                ->where('count', '<', 10)
                ->select()
                ->toArray();
            if (count($push)) {
                foreach ($push as $k => $v) {
                    $online = false;
                    if ($v['send_to'] == 1) {
                        if (GatewayService::isMINIUidOnline($v['u_id'])) {
                            $online = true;
                            GatewayService::sendToMiniClient($v['u_id'], json_decode($v['message'], true));
                        }
                    } else if ($v['send_to'] == 2) {
                        if (GatewayService::isDriverUidOnline($v['u_id'])) {
                            $online = true;
                            GatewayService::sendToDriverClient($v['u_id'], json_decode($v['message'], true));
                        }
                    }

                    if ($online) {
                        MiniPushT::update(['count' => $v['count'] + 1],
                            ['id' => $v['id']]);
                    }
                }
            }
        } catch (Exception $e) {
            LogService::save('handelMiniNoAnswer:' . $e->getMessage());
        }

    }

    /**
     * 司机处理推送请求
     */
    public
    function orderPushHandel($params)
    {
        try {
            $p_id = (int)($params['p_id']);
            $type = $params['type'];
            //修改推送表状态
            $push = Redis::instance()->hGet($p_id);
            //推送接受并回传
            Redis::instance()->hSet($p_id, 'state', 3);
            //从司机接受到信息且未操作队列中删除
            Redis::instance()->lRem('driver_receive_push', 100, $p_id);;

            $push_type = $push['type'];
            $order_id = $push['order_id'];
            $driver_id = $push['driver_id'];
            $f_d_id = $push['f_d_id'];

            if ($type == OrderEnum::ORDER_PUSH_AGREE) {
                //检测订单状态
                OrderT::update(['d_id' => $driver_id, 'state' => 2], ['id' => $order_id]);
                $order = $this->checkOrderState($order_id);
                $this->prefixPushAgree($driver_id, $order_id);

                //处理远程接驾费用
                $this->prefixFarDistance($order, $driver_id);
                if ($push_type == "normal") {
                    $this->sendToMini($order_id);
                } else
                    if ($push_type == "transfer") {
                        //释放转单司机
                        $this->prefixPushRefuse($f_d_id);
                        //处理原订单状态
                        //由触发器解决
                        $send_data = [
                            'type' => 'orderTransfer',
                            'order_info' => [
                                'id' => $order_id,
                                'u_id' => $f_d_id,
                                'msg' => '转单成功'
                            ]
                        ];
                        MiniPushT::create([
                            'u_id' => $f_d_id,
                            'message' => json_encode($send_data),
                            'count' => 1,
                            'state' => 1,
                            'send_to' => 2,
                            'o_id' => $order_id
                        ]);

                    }
            } else if ($type == OrderEnum::ORDER_PUSH_REFUSE) {
                $this->prefixPushRefuse($driver_id, $order_id);
            } else if ($type == OrderEnum::ORDER_PUSH_INVALID) {
                $this->prefixPushRefuse($driver_id, $order_id, 1);

            }
        } catch (Exception $e) {
            LogService::save("handel:" . $e->getTraceAsString());
        }


    }

    private
    function prefixFarDistance($order, $d_id)
    {
        // $order = $this->getOrder($o_id);
        if ($order->from == OrderEnum::FROM_DRIVER) {
            return true;
        }
        $location = $this->getDriverLocation($d_id);
        $far = $this->prefixFar($order->start_lng, $order->start_lat, $location['lng'], $location['lat']);
        $order->far_distance = $far['far_distance'];
        $order->far_money = $far['far_money'];
        $order->save();
    }


    private
    function prefixPushAgree($d_id, $order_id = 0)
    {
        //更新order表状态 - 用触发器：update_order_state 解决
        //更新司机状态:从正在派单移除；添加到已接单
        $company_id = (new DriverService())->getDriverCompanyId($d_id);
        /*   $redis = new \Redis();
           $redis->connect('127.0.0.1', 6379, 60);*/
        Redis::instance()->sRem('driver_order_no:' . $company_id, $d_id);
        Redis::instance()->sRem('driver_order_ing:' . $company_id, $d_id);
        Redis::instance()->sAdd('driver_order_receive:' . $company_id, $d_id);

        //将订单推送由正在处理集合改为已经完成集合
        Redis::instance()->sRem('order:ing', $order_id);
        Redis::instance()->sAdd('order:complete', $order_id);
    }

    private
    function sendToMini($order_id)
    {
        $order = $this->getOrder($order_id);
        $u_id = $order->u_id;
        $d_id = $order->d_id;

        if ($u_id) {
            $send_data = [
                'type' => 'order',
                'order_info' => [
                    'id' => $order_id,
                    'u_id' => $u_id,
                    'driver_name' => $order->driver->username,
                    'driver_phone' => $order->driver->phone,
                    'distance' => $this->getDriverDistance($order->start_lng, $order->start_lat, $d_id)
                ]
            ];
            MiniPushT::create(['u_id' => $u_id, 'message' => json_encode($send_data), 'count' => 1, 'o_id' => $order_id, 'state' => 1]);
            if (GatewayService::isMINIUidOnline($u_id)) {
                GatewayService::sendToMiniClient($u_id, $send_data);
            }
            //发送短消息
            (new SendSMSService())->sendMINISMS($order->phone, []);

        }

    }

    private
    function prefixPushRefuse($d_id, $order_id = 0, $revoke = 0)
    {
        $company_id = (new DriverService())->getDriverCompanyId($d_id);
        //更新司机状态:从正在派单移除；添加到未接单
        /*  $redis = new \Redis();
          $redis->connect('127.0.0.1', 6379, 60);*/
        Redis::instance()->sRem('driver_order_receive:' . $company_id, $d_id);
        Redis::instance()->sRem('driver_order_ing:' . $company_id, $d_id);
        Redis::instance()->sAdd('driver_order_no:' . $company_id, $d_id);

        //将订单由正在处理集合改为未处理集合
        Redis::instance()->sRem('order:ing', $order_id);
        Redis::instance()->sAdd('order:no', $order_id);

        //将司机加入巨拒单列表，不会重复发送
        if (!$revoke && $order_id) {
            Redis::instance()->sAdd("refuse:$order_id", $d_id);
        }
    }


    private
    function saveOrder($data)
    {
        $order = OrderT::create($data);
        if (!$order) {
            throw  new SaveException(['msg' => '下单失败']);
        }
        return $order;
    }

    private
    function saveOrderList($o_id, $state)
    {
        /*        $data = [
                    'o_id' => $o_id,
                    'state' => $state
                ];
                OrderListT::create($data);*/
        //将待处理订单写入待处理队列
        if ($state == OrderEnum::ORDER_LIST_NO) {
            Redis::instance()->sAdd('order:no', $o_id);
        } else if ($state == OrderEnum::ORDER_LIST_COMPLETE) {
            Redis::instance()->sAdd('order:complete', $o_id);
        } else if ($state == OrderEnum::ORDER_LIST_ING) {
            Redis::instance()->sAdd('order:ing', $o_id);
        }
    }

    private
    function findDriverToPush($order)
    {
        $redis = new \Redis();
        $redis->connect('121.37.255.12', 6379, 60);
        $redis->auth('waHqes-nijpi8-ruwqex');
        $company_id = $order['company_id'];
        //查询所有司机并按距离排序
        $lat = $order['start_lat'];
        $lng = $order['start_lng'];
        $order_id = $order['id'];
        $driver_location_key = BaseService::getLocationCacheKey($company_id);
        $list = $redis->rawCommand('georadius',
            $driver_location_key, $lng, $lat,
            config('setting.driver_nearby_km'),
            'km', 'ASC');
        if (!count($list)) {
            return CommonEnum::STATE_IS_FAIL;
        }
        $push = CommonEnum::STATE_IS_FAIL;
        //设置三个set: 司机未接单 driver_order_no；司机正在派单 driver_order_ing；司机已经接单 driver_order_receive
        foreach ($list as $k => $v) {
            $d_id = $v;
            $checkDriver = (new DriverService())->checkDriverCanReceiveOrder($d_id, $order['id']);
            if ($checkDriver) {
                $check = $this->checkDriverPush($order_id, $d_id);
                if ($check == 2) {
                    continue;
                }
                //将司机从'未接单'移除，添加到：正在派单
                $redis->sRem('driver_order_no:' . $company_id, $d_id);
                $redis->sRem('driver_order_receive:' . $company_id, $d_id);
                $redis->sAdd('driver_order_ing:' . $company_id, $d_id);

                //通过短信推送给司机
                $driver = DriverT::where('id', $d_id)->find();
                $phone = $driver->phone;
                (new SendSMSService())->sendOrderSMS($phone, ['code' => 'OK' . $order->order_num,
                    'order_time' => $order->create_time]);
                if ($order['from'] == "小程序下单" && $order['company_id'] == 1) {
                    (new SendSMSService())->sendOrderSMS("13515623335", ['code' => 'OK' . $order->order_num,
                        'order_time' => $order->create_time]);
                }
                $orderPush = OrderPushT::create(
                    [
                        'd_id' => $d_id,
                        'o_id' => $order->id,
                        'type' => 'normal',
                        'state' => OrderEnum::ORDER_PUSH_NO,
                        'limit_time' => time()
                    ]
                );
                $driver_location = $this->getDriverLocation($d_id, $company_id);
                //通过websocket推送给司机
                $push_data = [
                    'type' => 'order',
                    'order_info' => [
                        'o_id' => $order->id,
                        'from' => "系统派单",
                        'name' => $order->name,
                        'phone' => $order->phone,
                        'start' => $order->start,
                        'end' => $order->end,
                        'distance' => CalculateUtil::GetDistance($lat, $lng, $driver_location['lat'], $driver_location['lng']),
                        'create_time' => $order->create_time,
                        'p_id' => $orderPush->id

                    ]
                ];

                GatewayService::sendToDriverClient($d_id, $push_data);
                $orderPush->message = json_encode($push_data);
                $orderPush->save();
                $push = CommonEnum::STATE_IS_OK;
                break;
            }

        }
        return $push;
    }

    private
    function checkDriverPush($o_id, $d_id)
    {
        $pushes = OrderPushT::where('o_id', $o_id)
            ->where('d_id', $d_id)
            ->where('receive', CommonEnum::STATE_IS_OK)
            ->select()->toArray();
        if (!count($pushes)) {
            return 1;
        }
        foreach ($pushes as $k => $v) {
            if ($v['state'] == OrderEnum::ORDER_PUSH_REFUSE) {
                return 2;
            }
        }
        if (count($pushes) >= 3) {
            return 2;
        }
        return 1;
    }

    public
    function orderCancel($params)
    {
        try {
            Db::startTrans();
            $o_id = $params['id'];
            $grade = Token::getCurrentTokenVar('type');
            $order = $this->getOrder($o_id);

            //检查订单是否可以取消
            if ($order->begin == CommonEnum::STATE_IS_OK) {
                throw new UpdateException(['msg' => '订单已经开始，不能取消']);
            }
            $order->state = OrderEnum::ORDER_CANCEL;
            $order->cancel_remark = $params['remark'];
            $order->cancel_type = $grade;
            $res = $order->save();
            if (!$res) {
                throw new UpdateException();
            }
            //处理优惠券
            if (!empty($order->t_id)) {
                (new TicketService())->prefixTicketHandel($order->t_id, TicketEnum::STATE_NO);
            }

            //处理司机状态和推送状态
            $d_id = $this->withdraw($o_id, 'cancel');

            //通知司机
            if (($grade == 'mini' || $grade == 'manager') && $d_id) {
                $reason = $grade == 'mini' ? '用户取消订单' : '管理员取消订单';
                $reason .= ",取消原因：" . $params['remark'];
                $this->pushDriverWithOrderCancel($d_id, $reason);
            }

            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            LogService::save($e->getMessage());
            throw $e;
        }
    }


    public
    function orderBegin($params)
    {
        $o_id = $params['id'];
        //检测订单是否被取消
        $order = $this->checkOrderState($o_id, true);
        $order->begin = CommonEnum::STATE_IS_OK;
        if (!empty($params['start'])) {
            $order->start = CommonEnum::STATE_IS_OK;
        }
        if (!empty($params['start_lng'])) {
            $order->start_lng = CommonEnum::STATE_IS_OK;
        }
        if (!empty($params['start_lat'])) {
            $order->start_lat = CommonEnum::STATE_IS_OK;
        }

        $order->begin_time = date('Y-m-d H:i:s', time());
        $res = $order->save();
        if (!$res) {
            throw new UpdateException();
        }
    }

    public
    function beginWait($params)
    {
        $order = $this->checkOrderState($params['id'], true);
        $order->begin = CommonEnum::STATE_IS_OK;
        $order->begin_wait = date('Y-m-d H:i:s', time());
        $res = $order->save();
        if (!$res) {
            throw new UpdateException();
        }
    }

    /**
     * 到达起点
     */
    public
    function arrivingStart($id)
    {
        $order = $this->checkOrderState($id, true);
        $order->arriving_time = date('Y-m-d H:i:s', time());
        $res = $order->save();
        if (!$res) {
            throw new UpdateException();
        }
    }


    private
    function checkOrderState($o_id, $receive = true)
    {
        $order = OrderT::get($o_id);
        if ($order->state == OrderEnum::ORDER_CANCEL) {
            $msg = '订单已取消';
            if (!empty($order->cancel_type)) {
                $canceler = $order->cancel_type == 'mini' ? "下单用户" : "管理员";
                $msg = '订单已被' . $canceler . '取消,原因：' . $order->cancel_remark;
            }
            throw new UpdateException(['errorCode' => 40011, 'msg' => $msg]);
        }
        if ($receive) {
            if ($order->d_id != Token::getCurrentUid()) {
                throw new UpdateException(['errorCode' => 40012, 'msg' => '订单被转派或者撤回']);
            }
        }
        //检测订单是否被撤回
        $revoke = OrderRevokeT::where('o_id', $o_id)
            ->where('d_id', Token::getCurrentUid())
            ->count('id');
        if ($revoke) {
            throw new UpdateException(['errorCode' => 40012, 'msg' => '订单被撤回']);
        }
        return $order;
    }

    public
    function miniOrders($page, $size)
    {
        $u_id = Token::getCurrentUid();
        $orders = OrderT::miniOrders($u_id, $page, $size);
        return $orders;

    }

    public
    function miniOrder($id)
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
            $driver_location = $this->getDriverLocation($order->d_id, $order->company_id);
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

    public
    function driverCompleteOrder($params)
    {

        try {
            Db::startTrans();
            $id = $params['id'];
            $this->checkOrderComplete($id);
            //处理订单完成唯一性
            $distance = round($params['distance'] / 1000, 2);
            $wait_time = $params['wait_time'];
            $order = $this->getOrder($id);
            if (!empty($params['end'])) {
                $order->end = $params['end'];
            }
            if (!empty($params['end_lat'])) {
                $order->end_lat = $params['end_lat'];
            }
            if (!empty($params['end_lng'])) {
                $order->end_lng = $params['end_lng'];
            }

            if ($order->state == OrderEnum::ORDER_COMPLETE) {
                return $this->prepareOrderInfo($order);
            }

            if ($order->type == 2) {
                $money = $order->money;
                $ticket_money = 0;
            } else {
                $distance_money = $params['distance_money'];

                $wait_money = $params['wait_money'];
                if ($id == 5980 || $id == 5979 || $id == 6007) {
                    $wait_money = 130;
                }
                //处理恶劣天气费用
                $weather_money = $this->prefixWeather($distance_money);
                //处理订单金额

                $money = $distance_money + $wait_money + $weather_money + $order->far_money;

                $ticket_money = 0;
                if ($order->t_id) {
                    $ticket_money = $order->ticket->money;
                    $money -= $ticket_money;
                    //处理优惠券
                    $t_res = (new TicketService())->prefixTicketHandel($order->t_id, TicketEnum::STATE_USED);
                    if (!$t_res) {
                        Db::rollback();
                        throw new SaveException(['msg' => '保存处理优惠券失败']);
                    }
                }
                $order->distance = $distance;
                $order->distance_money = $distance_money;
                $order->ticket_money = $ticket_money;
                if ($id == 5980 || $id == 5979 || $id == 6007) {
                    $order->wait_time = 150;
                    $order->wait_money = 130;
                } else {
                    $order->wait_time = $wait_time;
                    $order->wait_money = $wait_money;
                }


                $order->weather_money = $weather_money;
                $order->money = $money;
                $order->end_time = date('Y-m-d H:i:s');
            }

            $order->state = OrderEnum::ORDER_COMPLETE;
            $res = $order->save();
            if (!$res) {
                Db::rollback();
                throw new SaveException(['msg' => '保存结算数据失败']);
            }
            /* //处理抽成
             if (!$this->prefixOrderCharge($id, $order->d_id, $order->company_id, $order->hotel_id, $money, $ticket_money)) {
                 Db::rollback();
                 throw new SaveException(['msg' => '订单抽成失败']);
             }*/
            Db::commit();

            Redis::instance()->hSet('driver:' . $order->d_id, 'order_time', time());
            (new DriverService())->handelDriveStateByComplete($order->d_id);
            (new WalletService())->checkDriverBalance(Token::getCurrentUid());
            $company_id = Token::getCurrentTokenVar('company_id');
            $company = $company_id == 1 ? 'OK' : '安心';
            $sendData = ['money' => $money,
                'company' => $company];
            if ($company_id == 1) {
                $sendData['phone'] = "19855751988";
            }
            (new SendSMSService())->sendOrderCompleteSMS($order->phone, $sendData);
            return $this->prepareOrderInfo($order);
        } catch (Exception $e) {
            LogService::save($e->getMessage());
            $this->deleteRedisOrderComplete($id);
            Db::rollback();
            throw $e;
        }

    }

    public function checkOrderComplete($order_id)
    {
        if ($this->checkRedisOrderComplete($order_id)) {
            throw new ParameterException(['msg' => '订单已完成，不能重复完成']);
        }
        $this->addRedisOrderComplete($order_id);

    }

    public function addRedisOrderComplete($order_id)
    {

        Redis::instance()->set($order_id, $order_id, 60);
    }

    public function checkRedisOrderComplete($order_id)
    {
        /*    $res = Redis::instance()->sIsMember('driver:complete', $order_id);
            return $res;*/
        $res = Redis::instance()->get($order_id);
        return $res;
    }

    public function deleteRedisOrderComplete($order_id)
    {
        Redis::instance()->sRem('driver:complete', $order_id);
    }

    public
    function prefixOrderCharge($o_id, $d_id, $company_id, $hotel_id, $money, $ticket_money)
    {
        $check = OrderMoneyT::where('o_id')->count('id');
        if ($check) {
            return 1;
        }
        $orderCharge = SystemOrderChargeT::where('company_id', $company_id)->find();
        $insurance = $orderCharge->insurance;
        $order = $orderCharge->order;
        $hotel = $orderCharge->hotel;
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
        if ($ticket_money) {
            array_push($data, [
                'o_id' => $o_id,
                'd_id' => $d_id,
                'money' => 0 - $ticket_money,
                'type' => 5,
            ]);
        }
        if ($hotel_id) {
            array_push($data, [
                'o_id' => $o_id,
                'd_id' => $d_id,
                'money' => $hotel,
                'type' => 7,
            ]);
        }
        $res = (new OrderMoneyT())->saveAll($data);
        return $res;

    }

    private
    function prefixWait($wait_time)
    {
        $wait = WaitPriceT::where('state', CommonEnum::STATE_IS_OK)
            ->find();
        if (!$wait || ($wait->free >= $wait_time)) {
            return 0;
        }

        return (ceil($wait_time / 60) - $wait->free) * $wait->price;


    }

    public
    function prefixWeather($distance_money)
    {
        $money = 0;
        $time_begin = '2020-01-22 12:00:00';
        $time_end = '2020-02-02 23:59:00';
        if (strtotime($time_begin) < time() && strtotime($time_end) > time()) {
            $money += 6;
        }
        $weather = WeatherT::find();
        if ((!$weather) || $weather->state == CommonEnum::STATE_IS_FAIL) {
            return $money;
        }
        $ratio = $weather->ratio - 1;
        $money += round($distance_money * $ratio);
        return $money;

    }

    private
    function prepareOrderInfo($order)
    {

        if ($order->state == OrderEnum::ORDER_COMPLETE) {
            $info = [
                'driver_name' => $order->driver->username,
                'driver_phone' => $order->driver->phone,
                'phone_code' => $order->driver->phone_code,
                'start' => $order->start,
                'start_lat' => $order->start_lat,
                'start_lng' => $order->start_lng,
                'end' => $order->end,
                'end_lat' => $order->end_lat,
                'end_lng' => $order->end_lng,
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
                'begin_time' => $order->begin_time,
                'end_time' => $order->end_time,

            ];
        } else {
            $info = [
                'driver_name' => $order->driver ? $order->driver->username : '',
                'driver_phone' => $order->driver ? $order->driver->phone : '',
                'start' => $order->start,
                'start_lat' => $order->start_lat,
                'start_lng' => $order->start_lng,
                'end' => $order->end,
                'end_lat' => $order->end_lat,
                'end_lng' => $order->end_lng,
                'from' => $order->from,
                'name' => $order->name,
                'phone' => $order->phone,
                'create_time' => $order->create_time,
                'state' => $order->state,
                'begin_time' => $order->begin_time,
            ];
        }

        return $info;

    }


    public
    function getDriverLocation($u_id, $company_id = '')
    {
        $redis = new \Redis();
        $redis->connect('121.37.255.12', 6379, 60);
        $redis->auth('waHqes-nijpi8-ruwqex');
        if (empty($company_id)) {
            $company_id = (new DriverService())->getDriverCompanyId($u_id);
        }
        $driver_location_key = BaseService::getLocationCacheKey($company_id);
        $location = $redis->rawCommand('geopos', $driver_location_key, $u_id);
        if ($location) {
            $lng = empty($location[0][0]) ? null : $location[0][0];
            $lat = empty($location[0][1]) ? null : $location[0][1];
        } else {
            $lng = null;
            $lat = null;
        }

        return [
            'lng' => $lng,
            'lat' => $lat,
        ];
    }

    private
    function getDriverDistance($user_lng, $user_lat, $d_id)
    {
        $location = $this->getDriverLocation($d_id);
        if ($location['lng'] && $location['lat']) {
            return CalculateUtil::GetDistance($user_lat, $user_lng, $location['lat'], $location['lng']);

        }
        return 0;

    }

    private
    function getOrder($id)
    {
        $order = OrderT::with(['ticket', 'driver'])->where('id', $id)->find();
        if (!$order) {
            throw new UpdateException(['msg' => '订单不存在']);
        }
        return $order;

    }

    public
    function transferOrder($params)
    {

        //检查订单是否开始
        $o_id = $params['id'];
        $order = $this->getOrder($o_id);
        if ($order->state != OrderEnum::ORDER_NO) {
            throw  new SaveException(['msg' => '订单已开始，不能转单']);
        }
        $d_id = $params['d_id'];
        //检查新司机状态是否有订单，修改司机状态
        if (!$this->updateDriverCanReceive($d_id, $o_id)) {
            throw  new SaveException(['msg' => '该司机有订单派送中，暂时不能接单']);
        }
        //计算距离和价格
        $distance_info = $this->getDistanceInfoToPush($order, $d_id);
        //新增推送状态
        $this->pushToDriverWithTransfer($d_id, $order, $distance_info);

    }

    private
    function updateDriverCanReceive($d_id, $order_id = 0)
    {
        //检查新司机状态是否有订单，修改司机状态
        /*        $redis = new \Redis();
                $redis->connect('127.0.0.1', 6379, 60);*/
        $company_id = (new DriverService())->getDriverCompanyId($d_id);
        $exits = Redis::instance()->sIsMember('driver_order_no:' . $company_id, "$d_id");
        if (!$exits) {
            return false;
        }
        //将被转单司机从'未接单'移除，添加到：正在派单
        Redis::instance()->sRem('driver_order_receive:' . $company_id, $d_id);
        Redis::instance()->sRem('driver_order_no:' . $company_id, $d_id);
        Redis::instance()->sAdd('driver_order_ing:' . $company_id, $d_id);

        //将订单状态改为处理中
        Redis::instance()->sRem('order:complete', $order_id);
        Redis::instance()->sRem('order:no:', $order_id);
        Redis::instance()->sAdd('order:ing', $order_id);
        return true;
    }

    /**
     * 向司机推送服务-转单服务-websocket/短信
     */
    private
    function pushToDriverWithTransfer($d_id, $order, $distance_info, $push_type = "transfer")
    {

        $from_name = '';
        if ($push_type == "transfer") {
            $from_name = $order->driver->username;

        } else if ($push_type == "manager") {
            $from_name = "管理员";
        }

        //通过短信推送给司机
        $driver = DriverT::where('id', $d_id)->find();
        $phone = $driver->phone;
        (new SendSMSService())->sendOrderSMS($phone, ['code' => 'OK' . $order->order_num,
            'order_time' => date('H:i',
                strtotime($order->create_time))]);


        //通过websocket推送给司机
        $push_data = [
            'from_type' => $push_type == "transfer" ? 'driver' : 'manager',
            'type' => 'transfer',
            'o_id' => $order->id,
            'd_id' => $d_id,
            'name' => $order->name,
            'company_id' => $order->company_id,
            'from' => $from_name,
            'phone' => $order->phone,
            'start' => $order->start,
            'end' => $order->end,
            'distance' => $distance_info['distance'],
            'distance_money' => 0,
            'create_time' => $order->create_time,
            'limit_time' => time(),
            'p_id' => $this->savePushCode($order->id, $d_id, 'transfer', $order->d_id)
        ];
        (new TaskService())->sendToDriverTask($push_data);
    }


    public
    function pushDriverWithOrderCancel($d_id, $reason)
    {
        //通过websocket推送给司机
        $push_data = [
            'type' => 'orderCancel',
            'order_info' => [
                'reason' => $reason
            ]
        ];
        GatewayService::sendToDriverClient($d_id, $push_data);
    }

    public
    function pushDriverWithOrderRevoke($d_id)
    {
        //通过websocket推送给司机
        $push_data = [
            'type' => 'orderRevoke',
            'order_info' => [
                'reason' => "订单已被被管理员撤回"
            ]
        ];

        GatewayService::sendToDriverClient($d_id, $push_data);
    }

    public
    function choiceDriverByManager($params)
    {
        $d_id = $params['d_id'];
        $o_id = $params['o_id'];
        //检测被推送司机状态
        if (!(new DriverService())->checkDriverOrderNo($d_id)) {
            throw new SaveException(['msg' => '司机已有订单，不能接单']);
        }

        //处理撤单状态：解决上次派单撤单之后，再派单给同-司机问题
        $cancel = OrderRevokeT::where('d_id', $d_id)->where('o_id', $o_id)->find();
        if ($cancel) {
            $cancel->delete();
        }

        //清除订单信息
        //1.清除司机信息
        $push = OrderPushT::where('o_id', $o_id)
            ->order('create_time')
            ->find();
        if ($push) {
            $o_d_id = $push->d_id;
            (new DriverService())->handelDriveStateByCancel($o_d_id);
        }
        //修改订单状态：转变为已经处理
        (new DriverService())->handelOrderStateToIng($o_id);

        //2.删除推送
        OrderPushT::where('o_id', $o_id)->delete();
        //推送给司机
        $d_id = $params['d_id'];
        $order = $this->getOrder($o_id);
        $distance_info = $this->getDistanceInfoToPush($order, $d_id);
        $this->pushToDriverWithTransfer($d_id, $order, $distance_info, "manager");

    }

    private
    function getDistanceInfoToPush($order, $d_id)
    {
        $distance = 0;
        $distance_money = 0;
        $start_lng = $order->start_lng;
        $start_lat = $order->start_lat;
        if (strlen($start_lng) && strlen($start_lat)) {
            $driver_location = $this->getDriverLocation($d_id);
            $distance = CalculateUtil::GetDistance($start_lat, $start_lng, $driver_location['lat'], $driver_location['lng']);
        }
        /*     $end_lng = $order->end_lng;
             $end_lat = $order->end_lat;
             if (strlen($end_lng) && strlen($end_lat)) {
                 $distance = CalculateUtil::GetDistance($start_lat, $start_lng, $end_lat, $end_lng);
             }
             $startRule = StartPriceT::where('type', 1)
                 ->where('state', CommonEnum::STATE_IS_OK)
                 ->order('order')
                 ->select();
             $distance_money = $this->prefixStartPriceWithDistance($distance, $startRule, 'start');*/

        return [
            'distance' => $distance,
            'distance_money' => $distance_money
        ];
    }

    public
    function driverOrders($page, $size, $time_begin, $time_end)
    {
        $d_id = Token::getCurrentUid();
        $orders = OrderT::getDriverOrders($d_id, $page, $size, $time_begin, $time_end);
        $orders['data'] = $this->prefixTransferInfo($orders['data']);
        $orders['statistic'] = $this->getDriverOrdersStatistic($d_id, $time_begin, $time_end);
        return $orders;

    }

    public
    function orderInfo($id)
    {
        $order = $this->getOrder($id);
        $info = $this->prepareOrderInfo($order);
        return $info;
    }

    public
    function managerOrders($page, $size, $driver, $time_begin, $time_end, $order_state, $order_from)
    {
        $grade = Token::getCurrentTokenVar('type');
        $company_id = Token::getCurrentTokenVar('company_id');
        if ($grade != 'manager') {
            throw new AuthException();
        }
        $orders = OrderV::managerOrders($company_id, $page, $size, $driver, $time_begin, $time_end, $order_state, $order_from);
        $orders['data'] = $this->prefixTransferInfo($orders['data']);
        $orders['statistic'] = $this->getManagerOrdersStatistic($company_id, $driver, $time_begin, $time_end);
        return $orders;


    }

    private
    function getDriverOrdersStatistic($d_id, $time_begin, $time_end)
    {
        $ordersMoney = OrderT::driverOrdersMoney($d_id, $time_begin, $time_end);
        return [
            'orders_count' => OrderT::driverOrderCount($d_id, $time_begin, $time_end),
            'all_money' => $ordersMoney['all_money'],
            'ticket_money' => $ordersMoney['ticket_money'],
        ];

    }

    private
    function getManagerOrdersStatistic($company_id, $driver, $time_begin, $time_end)
    {
        $ordersMoney = OrderV::ordersMoney($company_id, $driver, $time_begin, $time_end);
        return [
            // 'members' => OrderV::members($driver, $time_begin, $time_end),
            'members' => GatewayService::onlineDrivers($company_id),
            'orders_count' => OrderV::orderCount($company_id, $driver, $time_begin, $time_end),
            'all_money' => $ordersMoney['all_money'],
            'ticket_money' => $ordersMoney['ticket_money'],
        ];

    }

    private
    function prefixTransferInfo($data)
    {
        if (!count($data)) {
            return $data;
        }

        foreach ($data as $k => $v) {
            $data[$k]['phone_code'] = Token::getCurrentTokenVar('phone_code');
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

    public
    function recordsOfConsumption($page, $size, $phone)
    {
        $list = OrderT::recordsOfConsumption($page, $size, $phone);
        $list['statistic'] = $this->recordsOfConsumptionStatistic($phone);
        return $list;
    }

    private
    function recordsOfConsumptionStatistic($phone)
    {
        return [
            'count' => OrderT::ConsumptionCount($phone),
            'money' => OrderT::ConsumptionMoney($phone),

        ];

    }

    public
    function orderLocations($page, $size, $id)
    {
        $order = OrderT::get($id);
        $locations = LocationT::where('o_id', $id)
            ->where('begin', CommonEnum::STATE_IS_OK)
            ->field('lat,lng')
            ->paginate($size, false, ['page' => $page])->toArray();
        return [
            'start' => $order->start,
            'end' => $order->end,
            'state' => $order->state,
            'locations' => $locations
        ];
    }

    public
    function current($page, $size)
    {
        $company_id = 1;//Token::getCurrentTokenVar('company_id');
        $orders = Orderv::currentOrders($company_id, $page, $size);
        $orders['data'] = $this->prefixCurrentPush($orders['data']);
        return $orders;
    }

    private
    function prefixCurrentPush($data)
    {
        if (!empty($data)) {
            foreach ($data as $k => $v) {
                if (empty($v['d_id'])) {
                    $push = OrderPushT::where('o_id', $v['id'])
                        ->with(['driver' => function ($query) {
                            $query->field('id,username');
                        }])
                        ->order('create_time desc')->find();
                    if ($push) {
                        $data[$k]['push'] = [
                            'd_id' => $push->d_id,
                            'name' => $push->driver->username,
                            'create_time' => $push->create_time,
                            'state' => $push->state
                        ];
                    }
                }
            }

        }
        return $data;

    }


// 管理员撤回订单推送/一已经接单但未开始出发订单
    public
    function withdraw($o_id, $type = "revoke")
    {
        $order = OrderT::get($o_id);

        //1.检测订单是否被接单
        //2.修改司机状态
        //3.修改订单状态
        if ($order->begin == CommonEnum::STATE_IS_OK) {
            throw new UpdateException(['msg' => '订单已开始出发，不能被撤回']);
        }
        if ($order->d_id) {
            //解除司机订单关系
            $d_id = $order->d_id;
            if ($type == "revoke") {
                $order->d_id = '';
                $order->state = OrderEnum::ORDER_NO;
                $order->save();
            }
            //发送推送给司机说明订单撤销
            $this->pushDriverWithOrderRevoke($d_id);
        } else {

            $p_id = $o_id;
            //修改推送表状态
            $push = Redis::instance()->hGet($p_id);
            if ($push) {
                $d_id = $push['driver_id'];
                //处理推送取消
                Redis::instance()->delete($p_id);
            } else {
                $d_id = '';
            }
        }
        if ($d_id) {
            (new DriverService())->handelDriveStateByCancel($d_id, $o_id);
            //记录撤销记录
            if ($type == "revoke") {
                OrderRevokeT::create(['d_id' => $d_id, 'o_id' => $o_id]);
            }
            return $d_id;
        }

        return false;
    }

    public
    function CMSManagerOrders($page, $size, $driver, $time_begin, $time_end, $order_state, $order_from)
    {
        $company_id = Token::getCurrentTokenVar('company_id');
        $orders = OrderV::CMSManagerOrders($page, $size, $driver, $time_begin, $time_end, $order_state, $order_from, $company_id);
        $orders['statistic'] = $this->getManagerOrdersStatistic($company_id, $driver, $time_begin, $time_end);
        return $orders;


    }

    public
    function CMSInsuranceOrders($page, $size, $time_begin, $time_end)
    {
        $company_id = Token::getCurrentTokenVar('company_id');
        $orders = OrderV::CMSInsuranceOrders($page, $size, $time_begin, $time_end);
        $orders['statistic'] = OrderV::orderCount($company_id, '', $time_begin, $time_end);
        return $orders;

    }

    public
    function getOrderNumber()
    {
        $key = date('Ymd');
        if (Redis::instance()->exists($key)) {
            $value = Redis::instance()->get($key);
            $len = strlen($value);
            $number = $key;
            for ($i = 0; $i < 3 - $len; $i++) {
                $number .= '0';
            }
            $number .= $value;
        } else {
            Redis::instance()->set($key, 1);
            $number = $key . '001';
        }
        Redis::instance()->incre($key);
        return $number;
    }

    private function prefixDistance($id)
    {
        $locations = LocationT::where('o_id', $id)->select();
        $distance = 0;
        $old_lat = '';
        $old_lng = '';
        foreach ($locations as $k => $v) {
            if ($k == 0) {
                $old_lat = $v['lat'];
                $old_lng = $v['lng'];
                continue;
            }
            $distance += CalculateUtil::GetDistance($old_lat, $old_lng, $v['lat'], $v['lng']);
            $old_lat = $v['lat'];
            $old_lng = $v['lng'];
        }
        return $distance;
    }

}