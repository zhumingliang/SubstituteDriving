<?php

namespace app\api\controller\v1;

use app\api\model\DriverT;
use app\api\model\LocationLogT;
use app\api\model\LocationT;
use app\api\model\MiniPushT;
use app\api\model\OrderPushT;
use app\api\model\OrderRevokeT;
use app\api\model\OrderT;
use app\api\model\SmsRecordT;
use app\api\model\StartPriceT;
use app\api\model\TicketT;
use app\api\model\TimeIntervalT;
use app\api\model\UserT;
use app\api\model\WaitPriceT;
use app\api\model\WeatherT;
use app\api\service\DriverService;
use app\api\service\GatewayService;
use app\api\service\LogService;
use app\api\service\OrderService;
use app\api\service\SendSMSService;
use app\api\service\SystemPriceService;
use app\api\service\UserToken;
use app\lib\enum\CommonEnum;
use app\lib\enum\OrderEnum;
use app\lib\exception\SuccessMessage;
use app\lib\exception\SuccessMessageWithData;
use app\lib\Http;
use think\Exception;
use think\Queue;
use zml\tp_aliyun\SendSms;
use zml\tp_tools\CalculateUtil;
use zml\tp_tools\Redis;
use function GuzzleHttp\Psr7\str;

class Index
{
    public function index($d_id = 50)
    {

        $companyId = 1;
        $orderBeginTime = "2021-03-29 21:00:00";
        $interval = TimeIntervalT::where('company_id', $companyId)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->select()->toArray();

        $dateTime = strtotime($orderBeginTime);
        $day = date('Y-m-d', $dateTime);
        $price = 0;
        foreach ($interval as $k => $v) {
            $time_begin = strtotime($day . ' ' . $v['time_begin']);
            $time_end = strtotime($day . ' ' . $v['time_end']);
            if ($time_begin <= $dateTime && $dateTime <= $time_end) {
                $price = $v['price'];
                break;
            }
        }
        echo $price;

        /* echo (new OrderService())->getStartPrice(1, 10, 3240);


         define('PI', 3.1415926535898);
         define('EARTH_RADIUS', 6378.137);
         $data = [];
         $orders = OrderT::where('state', 4)->order('id desc')
             ->limit(0, 1)
             ->select();
         foreach ($orders as $k2 => $v2) {
             $locations = LocationT::where('o_id', $v2['id'])
                 ->order('baidu_time')->select();
             $la1 = '';
             $ln1 = '';
             $distance = 0;
             foreach ($locations as $k => $v) {

                 if ($k % 5 != 0) {
                     continue;
                 }

                 if ($k == 0) {
                     $la1 = $v['lat'];
                     $ln1 = $v['lng'];
                     continue;
                 }


                 $la2 = $v['lat'];
                 $ln2 = $v['lng'];
                 $distance += $this->GetDistance($la1, $ln1,
                     $la2, $ln2);

                 $la1 = $v['lat'];
                 $ln1 = $v['lng'];
             }

             array_push($data, [
                 '1' => $v2['distance'],
                 '2' => $distance
             ]);
         }

         print_r($data);*/


        /* $drivers = DriverT::where('state', CommonEnum::STATE_IS_OK)->select();
         foreach ($drivers as $k=>$v){
             $driver_id = 'driver:' . $v['id'];
             $data = [
                 'id' => $v['id'],
                 'number' =>$v['number'],
                 'username' => $v['username'],
                 'phone' => $v['phone'],
                 'company_id' => $v['company_id'],
                 'order_time' => time(),
             ];
             Redis::instance()->hMset($driver_id, $data);*/
    }

    //$this->mailTask($name);

    // echo CalculateUtil::GetDistance(30.95754, 117.85946, 30.960499, 117.847667);
    /*  $ticket = TicketT::where('company_id', 1)
      ->where('scene', 2)
      ->where('state', CommonEnum::STATE_IS_OK)
      ->whereTime('time_begin', '<=', date('Y-m-d H:i:s'))
      ->whereTime('time_end', '>=',  date('Y-m-d H:i:s'))
      ->order('create_time desc')
          ->fetchSql(true)
      ->find();
      print_r($ticket) ;*/

    /*   $locations = LocationT::where('o_id', 10442)->select();
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
       echo $distance;*/
    public function GetRange($lat, $lon, $raidus)
    {
        //计算纬度
        $degree = (24901 * 1609) / 360.0;
        $dpmLat = 1 / $degree;
        $radiusLat = $dpmLat * $raidus;
        $minLat = $lat - $radiusLat; //得到最小纬度
        $maxLat = $lat + $radiusLat; //得到最大纬度
        //计算经度
        $mpdLng = $degree * cos($lat * (PI / 180));
        $dpmLng = 1 / $mpdLng;
        $radiusLng = $dpmLng * $raidus;
        $minLng = $lon - $radiusLng; //得到最小经度
        $maxLng = $lon + $radiusLng; //得到最大经度
        //范围
        $range = array(
            'minLat' => $minLat,
            'maxLat' => $maxLat,
            'minLon' => $minLng,
            'maxLon' => $maxLng
        );
        return $range;
    }

//获取2点之间的距离
    public function GetDistance($lat1, $lng1, $lat2, $lng2)
    {
        $radLat1 = $lat1 * (PI / 180);
        $radLat2 = $lat2 * (PI / 180);
        $a = $radLat1 - $radLat2;
        $b = ($lng1 * (PI / 180)) - ($lng2 * (PI / 180));
        $s = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2)));
        $s = $s * EARTH_RADIUS;
        $s = round($s * 10000) / 10000;
        return $s;
    }

    public
    function log($msg = '')
    {
        {
            $data = [
                'phone' => \app\api\service\Token::getCurrentTokenVar('phone'),
                'msg' => $msg
            ];
            LocationLogT::create($data);
            return json(new SuccessMessage());
        }
    }
}