<?php


namespace zml\tp_tools;


class CalculateUtil
{
    public static $EARTH_RADIUS = 6378.137;//地球半径

    public static function rad($d)
    {
        return $d * pi() / 180.0;
    }

    /*
    * $lat1 A点经度
    * $lng1 A点维度
    * $lat2 B点经度
    * $lng2 B点经度
    * return 两点间距离 单位KM
    */
    public static function GetDistance($lat1, $lng1, $lat2, $lng2)
    {
        $radLat1 = self::rad($lat1);
        $radLat2 = self::rad($lat2);
        $a = $radLat1 - $radLat2;
        $b = self::rad($lng1) - self::rad($lng2);
        $s = 2 * asin(sqrt(pow(sin($a / 2), 2) +
                cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2)));
        $s = $s * self::$EARTH_RADIUS;
        $s = round($s * 10000) / 10000;
        return $s;
    }
}