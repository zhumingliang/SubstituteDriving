<?php


namespace app\api\model;


use think\Model;

class SmsRecordT extends Model
{
    protected $connection = 'db_service';

    public static function getList($sign, $phone, $state, $time_begin, $time_end, $page, $size)
    {
        $time_end = addDay(1, $time_end);
        $list = self:: where(function ($query) use ($sign) {
            if (!empty($sign)) {
                $query->where('sign', $sign);
            }
        })
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
            ->where(function ($query) use ($time_begin, $time_end) {
                if (!empty($time_begin) && !empty($time_end)) {
                    $query->whereBetweenTime('create_time', $time_begin, $time_end);

                }
            })
//            ->whereBetweenTime('create_time', $time_begin, $time_end)
            ->hidden(['sign', 'update_time', 'type', 'return_data'])
            ->order('create_time desc')
            ->paginate($size, false, ['page' => $page])->toArray();
        return $list;


    }

    public static function sendCount($sign)
    {

        $list = self::where('sign', $sign)
            ->field('count(id) as count,state')
            ->group('state')
            ->select();
        return $list;


    }


}