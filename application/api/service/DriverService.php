<?php


namespace app\api\service;


use app\api\model\DriverT;
use app\api\model\OrderT;
use app\api\model\WalletRecordV;
use app\lib\enum\CommonEnum;
use app\lib\enum\DriverEnum;
use app\lib\enum\OrderEnum;
use app\lib\exception\AuthException;
use app\lib\exception\SaveException;
use app\lib\exception\UpdateException;
use GatewayClient\Gateway;
use zml\tp_tools\Redis;

class DriverService
{
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

    public function drivers($page, $size, $time_begin, $time_end, $username, $account, $online)
    {

        $drivers = WalletRecordV::drivers($page, $size, $time_begin, $time_end, $username, $account, $online);
        return $drivers;

    }

    public function online($params)
    {
        $type = Token::getCurrentTokenVar('type');
        if ($type !== "driver") {
            throw new AuthException();
        }
        $id = Token::getCurrentUid();
        $this->prefixDriverState($params['line'], $id);
        $res = DriverT::update(['online' => $params['online']], ['id' => $id]);
        if (!$res) {
            throw new UpdateException();
        }

    }

    public function checkNoCompleteOrder($id)
    {
        $count = OrderT::where('d_id', $id)
            ->where('state', OrderEnum::ORDER_ING)
            ->count();
        return $count;

    }

    private function prefixDriverState($line_type, $d_id)
    {
        //处理司机状态
        //1.上线-添加进入未接单
        //2.下线-需要检测当前时候否有进行中的订单；清除接单三大状态
        $redis = new \Redis();
        $redis->connect('127.0.0.1', 6379, 60);
        if ($line_type == DriverEnum::ONLINE) {
            $redis->sAdd('driver_order_no', $d_id);
        } else {
            if ($this->checkNoCompleteOrder($d_id)) {
                throw new UpdateException(['您还有订单进行中，不能下线']);
            }

            $redis->sRem('driver_order_ing', $d_id);
            $redis->sRem('driver_order_no', $d_id);
            $redis->sRem('driver_order_receive', $d_id);
        }


    }

    public function checkDriverOrderNo($d_id)
    {
        $redis = new \Redis();
        $redis->connect('127.0.0.1', 6379, 60);
        $ret = $redis->sIsMember('driver_order_no', $d_id);
        return $ret;

    }

    /**
     * 订单完成修改司机接单状态
     * 接单中->未接单
     */
    public function handelDriveStateByComplete($d_id)
    {
        $redis = new \Redis();
        $redis->connect('127.0.0.1', 6379, 60);
        $redis->sRem('driver_order_receive', $d_id);
        $redis->sAdd('driver_order_no', $d_id);

    }

    /**
     * 司机接单修改司机接单状态
     * 未接单->已接单
     */
    public function handelDriveStateByReceive($d_id)
    {
        $redis = new \Redis();
        $redis->connect('127.0.0.1', 6379, 60);
        $redis->sRem('driver_order_no', $d_id);
        $redis->sAdd('driver_order_receive', $d_id);
    }

    /**
     * 司机接单修改司机接单状态
     * 未接单->正在派送
     */
    public function handelDriveStateByING($d_id)
    {
        $redis = new \Redis();
        $redis->connect('127.0.0.1', 6379, 60);
        $redis->sRem('driver_order_no', $d_id);
        $redis->sAdd('driver_order_ing', $d_id);
    }

    /**
     * 订单撤销修改司机接单状态
     * 接单/派单中->未接单
     */
    public function handelDriveStateByCancel($d_id)
    {
        $redis = new \Redis();
        $redis->connect('127.0.0.1', 6379, 60);
        $redis->sRem('driver_order_ing', $d_id);
        $redis->sRem('driver_order_receive', $d_id);
        $redis->sAdd('driver_order_no', $d_id);
    }

    public function acceptableOrder()
    {

        $redis = new Redis();
        $driver_ids = $redis->sMembers('driver_order_no');
        if (!$driver_ids) {
            return array();
        }

        $d_ids = implode(',', $driver_ids);
        $drivers = DriverT::field('id,username')->whereIn('id', $d_ids)->select();
        return $drivers;
    }

    /**
     * 获取司机附近所有司机
     */
    public function nearbyDrivers()
    {
        $km = config('setting.nearby_km');
        //1.获取本司机当前位置
        $d_id = Token::getCurrentUid();
        $driver_location = (new OrderService())->getDriverLocation($d_id);
        $drivers = $this->getDriversWithLocation($driver_location['lng'], $driver_location['lat'], $km);
        $order_no = $this->getDriverOrderNo();
        $drivers = $this->prefixDrivers($drivers, $order_no);
        return $drivers;
    }

    private function getDriversWithLocation($lng, $lat, $km)
    {
        $redis = new \Redis();
        $redis->connect('127.0.0.1', 6379, 60);
        //查询所有司机并按距离排序（包括在线和不在线）
        $list = $redis->rawCommand('georadius',
            'drivers_tongling', $lng, $lat, $km, 'km', 'WITHCOORD');
        return $list;
    }

    private function prefixDrivers($drivers, $order_no)
    {
        print_r($drivers);
        $online = array();
        $ids_arr = array();
        $order_no_arr = array();
        if (count($order_no)) {
            foreach ($order_no as $k => $v) {
                array_push($order_no_arr, $v);
            }
        }

        foreach ($drivers as $k => $v) {
            var_dump(Gateway::isUidOnline($v[0]));
            echo $v[0];
            if (Gateway::isUidOnline($v[0])) {
                $state = 2;//不可接单
                if (in_array($v[0], $order_no_arr)) {
                    $state = 1;//可以接单
                }
                array_push($online, [
                    'id' => $v[0],
                    'state' => $state,
                    'location' => $v[1]
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
                if ($v['id'] === $v2['id']) {
                    $online[$k]['username'] = $v2['username'];
                    unset($drivers_info[$k2]);
                    break;
                }
            }
        }
        return $online;

    }

    private function getDriverOrderNo()
    {
        $redis = new Redis();
        return $redis->sMembers('driver_order_no');
    }


}