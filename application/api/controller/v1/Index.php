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
        $drivers = DriverT::where('state', CommonEnum::STATE_IS_OK)->select();
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
            Redis::instance()->hMset($driver_id, $data);
        }

        //$this->mailTask($name);

        //echo (new OrderService())->getStartPrice(1, 10, 3240);
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