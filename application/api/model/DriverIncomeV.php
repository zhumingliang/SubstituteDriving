<?php


namespace app\api\model;


use app\lib\enum\OrderEnum;
use think\Model;

class DriverIncomeV extends Model
{
    public static function todayOrders($d_id)
    {
        $orders = self::where('d_id', $d_id)
            ->whereTime('create_time', 'd')
            ->where('state', OrderEnum::ORDER_COMPLETE)
            ->field('id,create_time,start,end,(money-cost) as money')
            ->select();
        return $orders;

    }

    public static function income($d_id, $day)
    {
        $money = self::where('d_id', $d_id)
            ->whereBetweenTime('create_time', $day)
            ->sum('ac_money');

        return $money;

    }

    public static function TimeIncome($d_id, $time_begin,$time_end)
    {
        $time_end=addDay(1,$time_end);
        $money = self::where('d_id', $d_id)
            ->whereBetweenTime('create_time', $time_begin,$time_end)
            ->sum('ac_money');
        return $money;

    }

}