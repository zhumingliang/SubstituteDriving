<?php


namespace app\api\model;


use think\Model;

class WalletRecordV extends Model
{
    public function getTypeAttr($value)
    {
        $state = [1 => "保险服务费", 2 => "订单服务费", 3 => "账户余额充值", 4 => "初始化", 5 => "优惠券返还", 6 => "代驾行驶费"];
        return $state[$value];
    }

    public static function drivers($page, $size, $time_begin, $time_end, $username, $account, $number, $online)
    {
        $time_end = addDay(1, $time_end);
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
            ->where(function ($query) use ($number) {
                if (strlen($number)) {
                    $query->where('number', 'like', '%' . $number . '%');
                }
            })
            ->where(function ($query) use ($online) {
                if ($online != 3) {
                    $query->where('online', '=', $online);
                }
            })
            ->where(function ($query) use ($time_begin, $time_end) {
                if (strlen($time_begin) && strlen($time_end)) {
                    $query->whereBetweenTime('create_time', $time_begin, $time_end);

                }
            })
            ->whereIn('type', '1,2,3,4,5')
            ->field('id ,account,number,username,phone,sum(money) as money,state,create_time')
            ->group('id')
            ->order('create_time desc')
            ->paginate($size, false, ['page' => $page]);
        return $list;

    }


    public static function recordsToDriver($page, $size, $d_id, $time_begin, $time_end)
    {
        $time_end = addDay(1, $time_end);
        $list = self::where('id', $d_id)
            ->where('type', '<>', 4)
            ->field('money,type,create_time')
            ->where(function ($query) use ($time_begin, $time_end) {
                if (strlen($time_begin) && strlen($time_end)) {
                    $query->whereBetweenTime('create_time', $time_begin, $time_end);

                }
            })
            ->order('create_time desc')
            ->paginate($size, false, ['page' => $page]);
        return $list;

    }

    public static function recordsToManager($page, $size)
    {
        $list = self::where('type', 3)
            ->field('d_id,money,username,create_time')
            ->order('create_time desc')
            ->paginate($size, false, ['page' => $page]);
        return $list;

    }

    public static function managerRecords($page, $size, $driver_id, $time_begin, $time_end)
    {
        $list = self::where(function ($query) use ($driver_id) {
            if ($driver_id) {
                $query->where('id', $driver_id);
            }
        })
            ->where(function ($query) use ($time_begin, $time_end) {
                if (strlen($time_begin) && strlen($time_end)) {
                    $time_end = addDay(1, $time_end);
                    $query->whereBetweenTime('create_time', $time_begin, $time_end);

                }
            })
            ->where('type', '<>', 4)
            ->field('id as d_id,username, money,create_time')
            ->order('create_time desc')
            ->paginate($size, false, ['page' => $page]);
        return $list;
    }

    public static function driverBalance($d_id)
    {
        $balance = self::where('id', $d_id)
            ->whereIn('type', '1,2,4,3,5')
            ->sum('money');
        return $balance;

    }

}