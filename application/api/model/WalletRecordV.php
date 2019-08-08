<?php


namespace app\api\model;


use think\Model;

class WalletRecordV extends Model
{
    public function getTypeAttr($value)
    {
        $state = [1 => "保险费用", 2 => "订单服务费", 3 => "账户余额充值", 4 => "初始化"];
        return $state[$value];
    }

    public static function drivers($page, $size, $time_begin, $time_end, $username, $account, $number,$online)
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
            ->field('id ,account,number,username,phone,sum(money) as money,state,create_time')
            ->group('id')
            ->order('create_time desc')
            ->paginate($size, false, ['page' => $page]);
        return $list;

    }


    public static function recordsToDriver($page, $size, $d_id)
    {
        $list = self::where('id', $d_id)
            ->field('money,type,create_time')
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

    public static function managerRecords($page, $size, $driver, $time_begin, $time_end)
    {
        $time_end = addDay(1, $time_end);
        $list = self::where(function ($query) use ($driver) {
            if (strlen($driver)) {
                $query->where('username', 'like', '%' . $driver . '%');
            }
        })
            ->where(function ($query) use ($time_begin, $time_end) {
                if (strlen($time_begin) && strlen($time_end)) {
                    $query->whereBetweenTime('create_time', $time_begin, $time_end);

                }
            })
            ->where('type', '<', 4)
            ->field('id as d_id,username, money,create_time')
            ->order('create_time desc')
            ->paginate($size, false, ['page' => $page]);
        return $list;
    }

    public static function driverBalance($d_id)
    {
        $balance = self::where('id', $d_id)
            ->sum('money');
        return $balance;

    }

}