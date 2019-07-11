<?php

namespace app\api\controller\v1;

use app\api\service\SendSMSService;

class Index
{
    public function index()
    {
        //(new SendSMSService())->sendOrderSMS('18956225230', ['code' => '*****' . substr('sajdlkjdsk21312', 5), 'order_time' => date('H:i', time())]);

        $this->zset();
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


        $distance = $redis->zScore('order:distance', 'o:3');
        if ($distance == false) {
            $res = $redis->zAdd('order:distance', 0, 'o:3');
            echo 'add:' . $res;
            return $res;
        }

        $res = $redis->zIncrBy('order:distance', $dis, 'o:3');
        echo 'save:' . $res;
        var_dump($redis->zScore('order:distance', 'o:3'));
    }

}
