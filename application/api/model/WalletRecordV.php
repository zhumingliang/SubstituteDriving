<?php


namespace app\api\model;


use think\Model;

class WalletRecordV extends Model
{
    public static function drivers($page, $size, $time_begin, $time_end, $username, $account, $online)
    {
        $list = self::where(function ($query) use ($username) {
            if (strlen($username)) {
                $query->where('username', 'like', '%' . $username . '%');
            }
        })
            ->where(function ($query) use ($account) {
                if (strlen($account)) {
                    $query->where('account', 'like', '%' . $account . '%');
                }
            })
            ->where(function ($query) use ($online) {
                if ($online !== 3) {
                    $query->where('online', '=', $online);
                }
            })
            ->where(function ($query) use ($time_begin, $time_end) {
                if (strlen($time_begin) && strlen($time_end)) {
                    $query->whereBetweenTime('create_time', $time_begin, $time_end);

                }
            })
            ->field('d_id,account,username,phone,sum(money) as money,state,create_time')
            ->group('d_id')
            ->order('create_time desc')
            ->paginate($size, false, ['page' => $page]);
        return $list;

    }

}