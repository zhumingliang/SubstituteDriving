<?php


namespace app\api\service;

use app\api\model\LocationT;

define('PI', 3.1415926535898);
define('EARTH_RADIUS', 6378.137);

class DistanceService
{
    public function getOrderDistance($orderId)
    {

        $locations = LocationT::where('o_id', $orderId)
            ->order('baidu_time')->select()->toArray();
        $la1 = '';
        $ln1 = '';
        $distance = 0;
        foreach ($locations as $k => $v) {

            if ($k != (count($locations)) - 1 && $k % 5 != 0) {
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
        return $distance;

    }

    //获取2点之间的距离
    private function GetDistance($lat1, $lng1, $lat2, $lng2)
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


}