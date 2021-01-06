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
use GatewayClient\Gateway;
use think\Db;
use think\Exception;
use zml\tp_tools\Redis;

class DriverService extends BaseService
{
    private $redis = null;

    public function __construct()
    {
       /* $this->redis = new \Redis();
        $this->redis->connect('127.0.0.1', 6379, 60);*/

        $this->redis = new \Redis();
        $this->redis->connect('121.37.255.12', 6379, 60);
        $this->redis->auth('waHqes-nijpi8-ruwqex');

    }

    public function save($params)
    {
        $params['pwd'] = sha1($params['pwd']);
        $params['admin_id'] = Token::getCurrentUid();
        $params['company_id'] = Token::getCurrentTokenVar('company_id');
        $driver = DriverT::create($params);
        if (!$driver) {
            throw new SaveException();
        }
        //将司机缓存到redis司机列表
        $this->saveDriverToCache($driver);
    }

    public function saveDriverToCache($driver)
    {
        $driver_id = 'driver:' . $driver->id;
        $data = [
            'id' => $driver->id,
            'number' => $driver->number,
            'username' => $driver->username,
            'phone' => $driver->phone,
            'company_id' => $driver->company_id,
            'order_time' => time(),
        ];
        Redis::instance()->hMset($driver_id, $data);
    }

    public function updateDriverToCache($driver)
    {
        $driver_id = 'driver:' . $driver['id'];
        $data = [];
        if (!empty($driver['number'])) {
            $data['number'] = $driver['number'];
        }
        if (!empty($driver['username'])) {
            $data['username'] = $driver['username'];
        }
        if (!empty($driver['phone'])) {
            $data['phone'] = $driver['phone'];
        }
        Redis::instance()->hMset($driver_id, $data);
    }

    public function drivers($page, $size, $time_begin, $time_end, $username, $account, $number, $online)
    {
        $company_id = Token::getCurrentTokenVar('company_id');
        $drivers = WalletRecordV::drivers($company_id, $page, $size, $time_begin, $time_end, $username, $account, $number, $online);
        return $drivers;

    }

    public function online($params)
    {
        try {
            Db::startTrans();
            $type = Token::getCurrentTokenVar('type');
            $id = Token::getCurrentUid();
            (new UserService())->checkDriverState($id);
            if ($type != "driver") {
                throw new AuthException();
            }
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
        $company_id = $this->getDriverCompanyId($d_id);
        if ($line_type == DriverEnum::ONLINE) {
            if ($this->redis->sIsMember('driver_order_ing:' . $company_id, $d_id)) {
                $this->redis->sRem('driver_order_ing:' . $company_id, $d_id);
            }

            if ($this->redis->sIsMember('driver_order_receive:' . $company_id, $d_id)) {
                $this->redis->sRem('driver_order_receive:' . $company_id, $d_id);
            }
            $this->redis->sAdd('driver_order_no:' . $company_id, $d_id);

        } else {
            if ($this->checkNoCompleteOrder($d_id)) {
                throw new UpdateException(['msg' => '您还有订单进行中，不能下线']);
            }

            if ($this->redis->sIsMember('driver_order_no:' . $company_id, $d_id)) {
                $this->redis->sRem('driver_order_no:' . $company_id, $d_id);
            }
            if ($this->redis->sIsMember('driver_order_ing:' . $company_id, $d_id)) {
                $this->redis->sRem('driver_order_ing:' . $company_id, $d_id);
            }
            if ($this->redis->sIsMember('driver_order_receive:' . $company_id, $d_id)) {
                $this->redis->sRem('driver_order_receive:' . $company_id, $d_id);
            }
        }


    }

    public
    function checkDriverOrderNo($d_id)
    {
        $company_id = $this->getDriverCompanyId($d_id);
        $ret = $this->redis->sIsMember('driver_order_no:' . $company_id, $d_id);
        return $ret;

    }

    /**
     * 订单完成修改司机接单状态
     * 接单中->未接单
     */
    public
    function handelDriveStateByComplete($d_id)
    {
        $company_id = $this->getDriverCompanyId($d_id);
        if ($this->redis->sIsMember('driver_order_receive:' . $company_id, $d_id)) {
            $this->redis->sRem('driver_order_receive:' . $company_id, $d_id);
        }
        if ($this->redis->sIsMember('driver_order_ing:' . $company_id, $d_id)) {
            $this->redis->sRem('driver_order_ing:' . $company_id, $d_id);
        }
        $this->redis->sAdd('driver_order_no:' . $company_id, $d_id);

    }

    /**
     * 司机接单修改司机接单状态
     * 未接单->已接单
     */
    public
    function handelDriveStateByReceive($d_id)
    {
        $company_id = $this->getDriverCompanyId($d_id);
        if ($this->redis->sIsMember('driver_order_no:' . $company_id, $d_id)) {
            $this->redis->sRem('driver_order_no:' . $company_id, $d_id);
        }
        if ($this->redis->sIsMember('driver_order_ing:' . $company_id, $d_id)) {
            $this->redis->sRem('driver_order_ing:' . $company_id, $d_id);
        }
        $this->redis->sAdd('driver_order_receive:' . $company_id, $d_id);
    }

    /**
     * 司机接单修改司机接单状态
     * 未接单->正在派送
     */
    public
    function handelDriveStateByING($d_id)
    {
        $company_id = $this->getDriverCompanyId($d_id);

        if ($this->redis->sIsMember('driver_order_no:' . $company_id, $d_id)) {
            $this->redis->sRem('driver_order_no:' . $company_id, $d_id);
        }
        if ($this->redis->sIsMember('driver_order_receive:' . $company_id, $d_id)) {
            $this->redis->sRem('driver_order_receive:' . $company_id, $d_id);
        }
        $this->redis->sAdd('driver_order_ing:' . $company_id, $d_id);
    }

    /**
     * 订单撤销修改司机接单状态
     * 接单/派单中->未接单
     */
    public
    function handelDriveStateByCancel($d_id, $order_id = 0)
    {
        $company_id = $this->getDriverCompanyId($d_id);
        if ($this->redis->sIsMember('driver_order_ing:' . $company_id, $d_id)) {
            $this->redis->sRem('driver_order_ing:' . $company_id, $d_id);
        }
        if ($this->redis->sIsMember('driver_order_receive:' . $company_id, $d_id)) {
            $this->redis->sRem('driver_order_receive:' . $company_id, $d_id);
        }
        $this->redis->sAdd('driver_order_no:' . $company_id, $d_id);


        $this->redis->sRem('order:no', $order_id);
        $this->redis->sRem('order:complete', $order_id);
        $this->redis->sRem('order:ing', $order_id);
    }

    public
    function handelOrderStateToIng($order_id)
    {
        $this->redis->sRem('order:no', $order_id);
        $this->redis->sRem('order:complete', $order_id);
        $this->redis->sAdd('order:ing', $order_id);
    }

    public
    function acceptableOrder($o_id)
    {
        $order = OrderT::get($o_id);
        if (!$order) {
            throw new ParameterException(['msg' => '订单不存在']);
        }
        $start_lng = $order->start_lng;
        $start_lat = $order->start_lat;

        $company_id = $order->company_id;
        $return_data = $this->acceptableManagerCreateOrder($start_lng, $start_lat, $company_id);
        return $return_data;
    }

    public
    function acceptableManagerCreateOrder($start_lng, $start_lat, $company_id = 0)
    {
        if (!$company_id) {
            $company_id = Token::getCurrentTokenVar('company_id');
        }
        $driver_location_key = self::getLocationCacheKey($company_id);

        $list = $this->redis->rawCommand('georadius', $driver_location_key, $start_lng, $start_lat, 100000, 'km', 'WITHDIST', 'WITHCOORD');

        $redis = new Redis();
        $driver_ids = $redis->sMembers("driver_order_no:$company_id");
        if (!$driver_ids || !count($list)) {
            return array();
        }

        $return_data = [];
        foreach ($list as $k => $v) {
            $d_id = $v[0];
            if (in_array($d_id, $driver_ids)
                && GatewayService::isDriverUidOnline($d_id)
                && $this->checkOnline($d_id)
            ) {

                $driver = $redis->lPop("driver:$d_id:location");
                if ($driver) {
                    $driver = json_decode($driver, true);
                } else {
                    $driver = DriverT::where('id', $d_id)->find()->toArray();
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
        return $return_data;
    }

    /**
     * 获取司机附近所有司机
     */
    public
    function nearbyDrivers($params)
    {
        $grade = Token::getCurrentTokenVar('type');
        $d_id = Token::getCurrentUid();
        $company_id = Token::getCurrentTokenVar('company_id');
        if ($grade == "driver") {
            $km = config('setting.driver_nearby_km');
            //1.获取本司机当前位置
            $driver_location = (new OrderService())->getDriverLocation($d_id, $company_id);
            $drivers = $this->getDriversWithLocation($company_id, $driver_location['lng'],
                $driver_location['lat'], $km);
        } else {
            $lng = $params['lng'];
            $lat = $params['lat'];
            $drivers = $this->getDriversWithLocation($company_id, $lng, $lat, '30000');
        }
        $order_no = $this->getDriverOrderNo($company_id);
        $drivers = $this->prefixDrivers($drivers, $order_no);
        return $drivers;
    }

    public
    function getDriversWithLocation($company_id, $lng = "114", $lat = "30", $km = "30000")
    {

        //查询所有司机并按距离排序（包括在线和不在线）
        $driver_location_key = self::getLocationCacheKey($company_id);
        $list = $this->redis->rawCommand('georadius',
            $driver_location_key, $lng, $lat, $km, 'km', 'WITHCOORD');
        return $list;
    }

    private
    function prefixDrivers($drivers, $order_no)
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
            if (GatewayService::isDriverUidOnline($v[0]) && $this->checkOnline($v[0])) {
                $state = 2;//不可接单
                if (in_array($v[0], $order_no_arr)) {
                    $state = 1;//可以接单
                }
                array_push($online, [
                    'id' => $v[0],
                    'state' => $state,
                    'username' => Redis::instance()->hGet('driver:' . $v[0], 'username'),
                    'location' => ['lng' => $v[1][0], 'lat' => $v[1][1]]
                ]);
                array_push($ids_arr, $v[0]);
            }
        }
        if (!count($online)) {
            return array();
        }
        return $online;

    }

    private
    function handelSort($data)
    {
        $value = array();
        for ($i = count($data) - 1; $i >= 0; $i--) {
            $value[] = $data[$i];
        }
        return $value;
    }

    private
    function getDriverOrderNo($company_id)
    {
        return $this->redis->sMembers('driver_order_no:' . $company_id);
    }

    public
    function onlineRecord($page, $size, $time_begin, $time_end, $online, $driver, $account)
    {
        $company_id = Token::getCurrentTokenVar('company_id');
        $list = OnlineRecordV::records($company_id, $page, $size, $time_begin, $time_end, $online, $driver, $account);
        return $list;
    }

    public
    function checkDriverHasUnCompleteOrder()
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

    public
    function income()
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

    //初始化企业司机状态
    public
    function init($company_id, $d_id)
    {
        if (empty($d_id)) {
            $drivers = DriverT::where('company_id', $company_id)
                ->where('state', CommonEnum::STATE_IS_OK)
                ->select()->toArray();
        } else {
            $drivers = DriverT::where('id', $d_id)
                ->where('company_id', $company_id)
                ->where('state', CommonEnum::STATE_IS_OK)
                ->select()->toArray();
        }

        foreach ($drivers as $k => $v) {
            $d_id = $v['id'];
            DriverT::update(['online' => CommonEnum::STATE_IS_FAIL], ['id' => $d_id]);
            if ($this->redis->sIsMember('driver_order_ing:' . $company_id, $d_id)) {
                $this->redis->sRem('driver_order_ing:' . $company_id, $d_id);
            }
            if ($this->redis->sIsMember('driver_order_receive:' . $company_id, $d_id)) {
                $this->redis->sRem('driver_order_receive:' . $company_id, $d_id);
            }
            if ($this->redis->sIsMember('driver_order_no:' . $company_id, $d_id)) {
                $this->redis->sRem('driver_order_no:' . $company_id, $d_id);
            }
        }
        // $company_id = $this->getDriverCompanyId($d_id);

    }

    public
    function getDriversCountWithLocation($company_id, $lat, $lng)
    {
        $count = 0;
        $redis = new \Redis();
        $km = config('setting.mini_nearby_km');
        $redis->connect('127.0.0.1', 6379, 60);
        $driver_location_key = self::getLocationCacheKey($company_id);

        $list = $redis->rawCommand('georadius', $driver_location_key, $lng, $lat, $km, 'km');
        $driver_ids = $this->redis->sMembers('driver_order_no:' . $company_id);
        if (!$driver_ids || !count($list)) {
            return 0;
        }

        foreach ($list as $k => $v) {
            $d_id = $v;
            if (in_array($d_id, $driver_ids) &&
                GatewayService::isDriverUidOnline($d_id) &&
                $this->checkOnline($d_id)
            ) {
                $count++;
            }
        }

        return $count;
    }

    public
    function checkOnline($d_id)
    {
        $driver = DriverT::where('id', $d_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->where('online', DriverEnum::ONLINE)
            ->count('id');
        return $driver;

    }

    public
    function checkDriverCanReceiveOrder($d_id, $order_id = 0)
    {
        if (!(GatewayService::isDriverUidOnline($d_id))) {
            return false;
        }
        if (!($this->checkOnline($d_id))) {
            return false;
        }
        $company_id = $this->getDriverCompanyId($d_id);
        if (!($this->redis->sIsMember('driver_order_no:' . $company_id, $d_id))) {
            return false;
        }
        if ($order_id && $this->redis->sIsMember("refuse:$order_id", $d_id)) {
            return false;
        }

        return true;
    }


    public
    function getDriverCompanyId($d_id)
    {
        $company_id = Redis::instance()->hGet('driver:' . $d_id, 'company_id');
        $company_id = empty($company_id) ? 1 : $company_id;
        return $company_id;
    }

}