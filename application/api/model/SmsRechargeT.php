<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class SmsRechargeT extends Model
{
    protected $connection = 'db_service';

    public static function rechargeCount($sign)
    {

        $count = self::where('sign', $sign)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->where('pay', CommonEnum::STATE_IS_OK)
            ->sum('count');
        return $count;

    }

    public static function recharges($sign, $page, $size, $time_begin, $time_end)
    {
        $time_end = addDay(1, $time_end);
        $list = self:: where(function ($query) use ($sign) {
            if (!empty($sign)) {
                $query->where('sign', $sign);
            }
        })
            ->whereBetweenTime('create_time', $time_begin, $time_end)
            ->order('create_time desc')
            ->paginate($size, false, ['page' => $page])->toArray();
        return $list;
    }

}