<?php


namespace app\api\service;


use app\api\model\DriverIncomeV;
use app\api\model\DriverT;
use app\api\model\OnlineRecordT;
use app\api\model\OnlineRecordV;
use app\api\model\OrderT;
use app\api\model\WalletRecordV;
use app\lib\enum\CommonEnum;
use app\lib\enum\DriverEnum;
use app\lib\enum\OrderEnum;
use app\lib\exception\AuthException;
use app\lib\exception\ParameterException;
use app\lib\exception\SaveException;
use app\lib\exception\UpdateException;
use think\Db;
use think\Exception;
use zml\tp_tools\Redis;

class DriverService
{
    private $redis = null;

    public function __construct()
    {
        $this->redis = new \Redis();
        $this->redis->connect('127.0.0.1', 6379, 60);

    }

    public function save($params)
    {
        $params['pwd'] = sha1($params['pwd']);
        $params['admin_id'] = Token::getCurrentUid();
        $params['state'] = CommonEnum::STATE_IS_OK;
        $driver = DriverT::create($params);
        if (!$driver) {
            throw new SaveException();
        }

    }

    public function drivers($page, $size, $time_begin, $time_end, $username, $account, $number, $online)
    {

        $drivers = WalletRecordV::drivers($page, $size, $time_begin, $time_end, $username, $account, $number, $online);
        return $drivers;

    }

    public function online($params)
    {
        try {

            Db::startTrans();
            $type = Token::getCurrentTokenVar('type');
            if ($type !== "driver") {
                throw new AuthException();
            }
            $id = Token::getCurrentUid();
            $driver = DriverT::get($id);
            if ($driver->online == $params['online']) {
                Db::commit();
                return true;
            }
            $online_begin = $driver->last_online_time;
            $driver->online = $params['online'];
            if ($params['online'] == DriverEnum::ONLINE) {
                if ((new WalletService())->checkDriverBalance(Token::getCurrentUid())) {
                    Db::rollback();
                    throw new UpdateException(['msg' => '余额不足,不能上线']);
                }

                $driver->last_online_time = date('Y-m-d H:i:s');
            } else {
                $driver->online_time = $driver->online_time + (time() - strtotime($online_begin));
                if (!$this->saveOnlineRecord($id, $online_begin, date('Y-m-d H:i:s'))) {
                    Db::rollback();
                    throw new UpdateException();
                }
            }

            $res = $driver->save();
            if (!$res) {
                Db::rollback();
                throw new UpdateException();
            }

            $this->prefixDriverState($params['online'], $id);
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            throw $e;
        }
    }


    public function checkNoCompleteOrder($id)
    {
        $count = OrderT::where('d_id', $id)
            ->where('state', OrderEnum::ORDER_ING)
            ->count();
        return $count;

    }

    private function saveOnlineRecord($d_id, $online_begin, $online_end)
    {
        $money = OrderT::where('d_id', $d_id)
            ->whereBetweenTime('create_time', $online_begin, $online_end)
            ->where('state', OrderEnum::ORDER_COMPLETE)
            ->sum('money');
        $count = OrderT::where('d_id', $d_id)
            ->whereBetweenTime('create_time', $online_begin, $online_end)
            ->where('state', OrderEnum::ORDER_COMPLETE)
            ->count('id');

        $data = [
            'd_id' => $d_id,
            'state' => CommonEnum::STATE_IS_OK,
            'online_begin' => $online_begin,
            'online_end' => $online_end,
            'money' => $money,
            'count' => $count
        ];
        return OnlineRecordT::create($data);

    }


    private function prefixDriverState($line_type, $d_id)
    {
        //处理司机状态
        //1.上线-添加进入未接单
        //2.下线-需要检测当前时候否有进行中的订单；清除接单三大状态
        if ($line_type == DriverEnum::ONLINE) {
            if ($this->redis->sIsMember('driver_order_ing', $d_id)) {
                $this->redis->sRem('driver_order_ing', $d_id);
            }
            if ($this->redis->sIsMember('driver_order_receive', $d_id)) {
                $this->redis->sRem('driver_order_receive', $d_id);
            }
            $this->redis->sAdd('driver_order_no', $d_id);

        } else {
            if ($this->checkNoCompleteOrder($d_id)) {
                throw new UpdateException(['您还有订单进行中，不能下线']);
            }

            if ($this->redis->sIsMember('driver_order_no', $d_id)) {
                $this->redis->sRem('driver_order_no', $d_id);
            }
            if ($this->redis->sIsMember('driver_order_ing', $d_id)) {
                $this->redis->sRem('driver_order_ing', $d_id);
            }
            if ($this->redis->sIsMember('driver_order_receive', $d_id)) {
                $this->redis->sRem('driver_order_receive', $d_id);
            }
        }


    }

    public function checkDriverOrderNo($d_id)
    {
        $ret = $this->redis->sIsMember('driver_order_no', $d_id);
        return $ret;

    }

    /**
     * 订单完成修改司机接单状态
     * 接单中->未接单
     */
    public function handelDriveStateByComplete($d_id)
    {
        if ($this->redis->sIsMember('driver_order_receive', $d_id)) {
            $this->redis->sRem('driver_order_receive', $d_id);
        }
        $this->redis->sAdd('driver_order_no', $d_id);

    }

    /**
     * 司机接单修改司机接单状态
     * 未接单->已接单
     */
    public function handelDriveStateByReceive($d_id)
    {
        if ($this->redis->sIsMember('driver_order_no', $d_id)) {
            $this->redis->sRem('driver_order_no', $d_id);
        }
        $this->redis->sAdd('driver_order_receive', $d_id);
    }

    /**
     * 司机接单修改司机接单状态
     * 未接单->正在派送
     */
    public function handelDriveStateByING($d_id)
    {
        if ($this->redis->sIsMember('driver_order_no', $d_id)) {
            $this->redis->sRem('driver_order_no', $d_id);
        }
        $this->redis->sAdd('driver_order_ing', $d_id);
    }

    /**
     * 订单撤销修改司机接单状态
     * 接单/派单中->未接单
     */
    public function handelDriveStateByCancel($d_id)
    {
        if ($this->redis->sIsMember('driver_order_ing', $d_id)) {
            $this->redis->sRem('driver_order_ing', $d_id);
        }
        if ($this->redis->sIsMember('driver_order_receive', $d_id)) {
            $this->redis->sRem('driver_order_receive', $d_id);
        }
        $this->redis->sAdd('driver_order_no', $d_id);
    }

    public function acceptableOrder($o_id)
    {

        $order = OrderT::get($o_id);
        if (!$order) {
            throw new ParameterException(['msg' => '订单不存在']);
        }
        $start_lng = $order->start_lng;
        $start_lat = $order->start_lat;

        $list = $this->redis->rawCommand('georadius',
            'drivers_tongling', $start_lng, $start_lat, 100000, 'km', 'WITHDIST', 'WITHCOORD');

        $redis = new Redis();
        $driver_ids = $redis->sMembers('driver_order_no');
        if (!$driver_ids || !count($list)) {
            return array();
        }

        $return_data = [];
        foreach ($list as $k => $v) {
            $d_id = $v[0];
            if (in_array($d_id, $driver_ids)) {
                $driver = $redis->rPop("driver:$d_id:location");
                if ($driver) {
                    $driver = json_decode($driver, true);
                }
                $data = [
                    'id' => $d_id,
                    'distance' => $v[1],
                    'name' => empty($driver['username']) ? '' : $driver['username'],
                    'phone' => empty($driver['phone']) ? '' : $driver['phone'],
                    'citycode' => empty($driver['citycode']) ? '' : $driver['citycode'],
                    'city' => empty($driver['city']) ? '' : $driver['city'],
                    'district' => empty($driver['district']) ? '' : $driver['district'],
                    'street' => empty($driver['street']) ? '' : $driver['street'],
                    'addr' => empty($driver['addr']) ? '' : $driver['addr'],
                    'locationdescribe' => empty($driver['locationdescribe']) ? '' : $driver['locationdescribe'],
                    'location' => $v[2]
                ];
                array_push($return_data, $data);
            }
        }

        /* $d_ids = implode(',', $driver_ids);
         $drivers = DriverT::field('id,username')->whereIn('id', $d_ids)->select();*/

        return $return_data;
    }

    public function acceptableManagerCreateOrder($start_lng, $start_lat)
    {
        $list = $this->redis->rawCommand('georadius',
            'drivers_tongling', $start_lng, $start_lat, 100000, 'km', 'WITHDIST', 'WITHCOORD');

        $redis = new Redis();
        $driver_ids = $redis->sMembers('driver_order_no');
        if (!$driver_ids || !count($list)) {
            return array();
        }

        $return_data = [];
        foreach ($list as $k => $v) {
            $d_id = $v[0];
            if (in_array($d_id, $driver_ids)) {
                $driver = $redis->rPop("driver:$d_id:location");
                if ($driver) {
                    $driver = json_decode($driver, true);
                }
                $data = [
                    'id' => $d_id,
                    'distance' => $v[1],
                    'name' => empty($driver['username']) ? '' : $driver['username'],
                    'phone' => empty($driver['phone']) ? '' : $driver['phone'],
                    'citycode' => empty($driver['citycode']) ? '' : $driver['citycode'],
                    'city' => empty($driver['city']) ? '' : $driver['city'],
                    'district' => empty($driver['district']) ? '' : $driver['district'],
                    'street' => empty($driver['street']) ? '' : $driver['street'],
                    'addr' => empty($driver['addr']) ? '' : $driver['addr'],
                    'locationdescribe' => empty($driver['locationdescribe']) ? '' : $driver['locationdescribe'],
                    'location' => $v[2]
                ];
                array_push($return_data, $data);
            }
        }

        /* $d_ids = implode(',', $driver_ids);
         $drivers = DriverT::field('id,username')->whereIn('id', $d_ids)->select();*/

        return $return_data;
    }

    /**
     * 获取司机附近所有司机
     */
    public function nearbyDrivers($params)
    {
        $grade = 1;//Token::getCurrentTokenVar('type');
        $d_id = 1;//Token::getCurrentUid();
        if ($grade == "driver") {
            $km = config('setting.nearby_km');
            //1.获取本司机当前位置
            $driver_location = (new OrderService())->getDriverLocation($d_id);
            $drivers = $this->getDriversWithLocation($driver_location['lng'], $driver_location['lat'], $km);

        } else {
            $lng = $params['lng'];
            $lat = $params['lat'];
            $drivers = $this->getDriversWithLocation($lng, $lat);
        }
        print_r($drivers);

        $order_no = $this->getDriverOrderNo();
        print_r($order_no);
        $drivers = $this->prefixDrivers($drivers, $order_no);
        return $drivers;
    }

    private function getDriversWithLocation($lng = "114", $lat = "30", $km = "30000")
    {

        //查询所有司机并按距离排序（包括在线和不在线）
        $list = $this->redis->rawCommand('georadius',
            'drivers_tongling', $lng, $lat, $km, 'km', 'WITHCOORD');
        return $list;
    }

    private function prefixDrivers($drivers, $order_no)
    {
        $online = array();
        $ids_arr = array();
        $order_no_arr = array();
        if (count($order_no)) {
            foreach ($order_no as $k => $v) {
                array_push($order_no_arr, $v);
            }
        }

        foreach ($drivers as $k => $v) {
            echo $v[0].'-'.GatewayService::isDriverUidOnline($v[0]);
            if (GatewayService::isDriverUidOnline($v[0])) {

                $state = 2;//不可接单
                if (in_array($v[0], $order_no_arr)) {
                    $state = 1;//可以接单
                }
                array_push($online, [
                    'id' => $v[0],
                    'state' => $state,
                    'location' => ['lng' => $v[1][0], 'lat' => $v[1][1]]
                ]);
                array_push($ids_arr, $v[0]);
            }
        }
        if (!count($online)) {
            return array();
        }
        $ids = implode(',', $ids_arr);
        $drivers_info = DriverT::field('id,username')->whereIn('id', $ids)->select();
        foreach ($online as $k => $v) {
            foreach ($drivers_info as $k2 => $v2) {
                if ($v['id'] == $v2->id) {
                    $online[$k]['username'] = $v2->username;
                    // unset($drivers_info[$k2]);
                    break;
                }
            }
        }
        return $online;

    }

    private function getDriverOrderNo()
    {
        return $this->redis->sMembers('driver_order_no');
    }

    public function onlineRecord($page, $size, $time_begin, $time_end, $online, $driver, $account)
    {
        $list = OnlineRecordV::records($page, $size, $time_begin, $time_end, $online, $driver, $account);
        return $list;
    }

    public function checkDriverHasUnCompleteOrder()
    {
        $d_id = Token::getCurrentUid();
        $info = [];
        $order = OrderT::where('d_id', $d_id)
            ->whereIn('state', OrderEnum::ORDER_ING)
            ->find();
        if ($order) {
            $info = [
                'id' => $order->id,
                'state' => $order->state,
                'start' => $order->start,
                'end' => $order->end,
                'begin' => $order->begin,
                'name' => $order->name,
                'phone' => $order->phone,
                'create_time' => $order->create_time,
                'arriving_time' => $order->arriving_time,
                'receive_time' => $order->receive_time,
                'start_lng' => $order->start_lng,
                'start_lat' => $order->start_lat,
                'end_lng' => $order->end_lng,
                'end_lat' => $order->end_lat
            ];

        }
        return $info;
    }

    public function income()
    {

        $today = date('Y-m-d', time());
        $yesterday = reduceDay(1, $today);
        $d_id = Token::getCurrentUid();
        $today_income = DriverIncomeV::income($d_id, $today);
        $yesterday_income = DriverIncomeV::income($d_id, $yesterday);
        return [
            'yesterday' => $yesterday_income,
            'today' => $today_income,
            'today_orders' => DriverIncomeV::todayOrders($d_id)
        ];
    }

    public function init($d_id)
    {
        DriverT::update(['online' => CommonEnum::STATE_IS_FAIL], ['id' => $d_id]);
        if ($this->redis->sIsMember('driver_order_ing', $d_id)) {
            $this->redis->sRem('driver_order_ing', $d_id);
        }
        if ($this->redis->sIsMember('driver_order_receive', $d_id)) {
            $this->redis->sRem('driver_order_receive', $d_id);
        }
        if ($this->redis->sIsMember('driver_order_no', $d_id)) {
            $this->redis->sRem('driver_order_no', $d_id);
        }
    }


}