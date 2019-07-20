<?php


namespace app\api\model;


use think\Model;

class OnlineRecordV extends Model
{
    public static function records($page, $size, $time_begin, $time_end, $online, $driver, $account)
    {

        $list = self::where(function ($query) use ($online) {
            if ($online < 3) {
                $query->where('online', '=', $online);
            }
        })
            ->where(function ($query) use ($time_begin, $time_end) {
                if (strlen($time_begin) && strlen($time_end)) {
                    $query->whereBetweenTime('last_online_time', $time_begin, $time_end);
                }
            })
            ->where(function ($query) use ($driver) {
                if (strlen($driver)) {
                    $query->where('name', 'like', '%' . $driver . '%');
                }
            })
            ->where(function ($query) use ($account) {
                if (strlen($account)) {
                    $query->where('account', 'like', '%' . $account . '%');
                }
            })
            ->order('create_time desc')
            ->paginate($size, false, ['page' => $page])->toArray();
        return $list;
    }

}