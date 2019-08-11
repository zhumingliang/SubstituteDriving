<?php

namespace app\api\controller\v1;

use app\api\model\DriverT;
use app\api\model\StartPriceT;
use app\api\model\TimeIntervalT;
use app\api\model\WaitPriceT;
use app\api\service\SendSMSService;

class Index
{
    public function index()
    {
        //(new SendSMSService())->sendOrderSMS('18956225230', ['code' => '*****' . substr('sajdlkjdsk21312', 5), 'order_time' => date('H:i', time())]);

        // $this->initDriverStatus();
        $menus = [
            [
                "name" => "预约代驾",
                "sub_button" => [
                    [
                        "type" => "miniprogram",
                        "name" => "立即下单",
                        "url" => "http://mp.weixin.qq.com",
                        "appid" => "wx286b93c14bbf93aa",
                        "pagepath" > "pages/lunar/index"
                    ]
                ]
            ],
            [
                "type" => "click",
                "name" => "资费标准",
                "key" => "fee"
            ],
            [
                "type" => "click",
                "name" => "联系我们",
                "key" => "contact"
            ]
        ];
        return json($menus);
    }

    private function prefixFee()
    {
        $wait = WaitPriceT::find();
        $wait_msg = "  免费等候" . $wait->free . "分钟，等候超出" . $wait->free . "分钟后每1分钟加收" . $wait->price . "元。";
        $fee_msg = "";
        $interval = TimeIntervalT::select();
        $start = StartPriceT::where('type', 1)->select();
        if (!empty($interval)) {
            foreach ($interval as $k => $v) {
                $fee_msg .= "  时间：(" . $v->time_begin . "-" . $v->time_end . ")" . "起步价" . $v->price . "元";
                $d = 0;
                foreach ($start as $k2 => $v2) {

                    if ($k2 == 0) {
                        $fee_msg .= "（" . $v2->distance . "公里内包含" . $v2->distance . "公里）;" . "\n";
                    } else if ($k2 == 1) {
                        $d += $v2->distance;
                        $fee_msg .= "超出起步里程后," . $v2->distance . "公里内包含" . $v2->distance . "公里,加收" . $v2->price . "元；";
                    } else {
                        $fee_msg .= "超出起步里程" . $d . "公里后," . $v2->distance . "公里内包含" . $v2->distance . "公里,加收" . $v2->price . "元；";
                        $d += $v2->distance;
                    }

                }
                $fee_msg .= "\n";
            }
        }

        return "资费标准：\n" . $fee_msg . $wait_msg;
    }

    public function initDriverStatus()
    {
        $redis = new \Redis();
        $redis->connect('127.0.0.1', 6379, 60);
        $drivers = DriverT::all();
        foreach ($drivers as $k => $v) {
            $redis->sRem('driver_order_ing', $v['id']);
            $redis->sAdd('driver_order_no', $v['id']);
        }

    }

    public function send($client_id)
    {
        //  var_dump(Gateway::sendToClient($client_id, 1));
    }


    public function locationAdd($lat, $lng, $d_id)
    {


        /*  $redis = new \Redis();
          $redis->connect('127.0.0.1', 6379, 60);

          $ret = $redis->rawCommand('geoadd', 'drivers_tongling', $lat, $lng, $d_id);
          var_dump($ret);*/

    }

    public function location($d_id)
    {
        $redis = new \Redis();
        $redis->connect('127.0.0.1', 6379, 60);
        print_r($redis->rawCommand('geopos', 'drivers_tongling', $d_id));
    }

    public function deleteLocation($d_id)
    {
        $redis = new \Redis();
        $redis->connect('127.0.0.1', 6379, 60);
        var_dump($redis->rawCommand('zrem', 'drivers_tongling', $d_id));
    }

    public function radius($lat, $lng, $type)
    {
        $redis = new \Redis();
        $redis->connect('127.0.0.1', 6379, 60);
        //查询所有司机并按距离排序
        //$list = $redis->rawCommand('georadius', 'drivers_tongling');
        $list = $redis->rawCommand('georadius', 'drivers_tongling', $lng, $lat, '1000000', 'km', $type);
        print_r($list);
    }

    public function zset()
    {
        $dis = 1.1;
        $redis = new \Redis();
        $redis->connect('127.0.0.1', 6379, 60);
        /*        var_dump($redis->zScore('order:distance', 'o:2'));
                // $res = $redis->zAdd('order:distance', 0, 'o:2');
                //var_dump($res);
                $distance = $redis->zScore('order:distance', 'o:2');
                var_dump($distance);
                $redis->zIncrBy('order:distance', $dis, 'o:2');
                $distance = $redis->zScore('order:distance', 'o:2');
                var_dump($distance);
                $distance = $redis->zScore('order:distance', 'o:1');
                var_dump($distance);*/


    }

}
