<?php


namespace app\api\model;


use think\Model;

class SmsRecordT extends Model
{
    protected $connection = 'db_service';

    public static function getList($sign, $phone, $state, $time_begin, $time_end, $page, $size)
    {
        $time_end = addDay(1, $time_end);
        $list = self::where('sign', $sign)
            ->where(function ($query) use ($state) {
                if ($state < 3) {
                    $query->where('state', $state);
                }
            })
            ->where(function ($query) use ($phone) {
                if (!empty($phone)) {
                    $query->where('phone', $phone);
                }
            })
            ->whereBetweenTime('create_time', $time_begin, $time_end)
            ->order('create_time desc')
            ->paginate($size, false, ['page' => $page])->toArray();
        return $list;


    }

}