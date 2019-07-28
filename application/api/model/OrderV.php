<?php


namespace app\api\model;


use app\lib\enum\OrderEnum;
use think\Model;

class OrderV extends Model
{
    public function getFromAttr($value)
    {
        if ($value) {
            $data = [1 => '小程序下单', 2 => '司机自主简单', 3 => '管理员自主建单', 4 => '公众号下单'];
            return $data[$value];
        }

    }
    public function getCancelTypeAttr($value)
    {
        if ($value) {
            $data = ['mini' => '乘客', 'driver' => '司机', 'manager' => '管理员'];
            return $data[$value];
        }

    }

    public static function CMSManagerOrders($page, $size, $driver, $time_begin, $time_end, $order_state, $order_from)
    {
        $list = self::where(function ($query) use ($order_state) {
            if ($order_state < 6) {
                $query->where('state', '=', $order_state);
            }
        })->where(function ($query) use ($order_from) {
            if ($order_from < 5) {
                $query->where('from', '=', $order_from);
            }
        })
            ->where(function ($query) use ($driver) {
                if (strlen($driver)) {
                    $query->where('driver', 'like', '%' . $driver . '%');
                }
            })
            ->where(function ($query) use ($time_begin, $time_end) {
                if (strlen($time_begin) && strlen($time_end)) {
                    $query->whereBetweenTime('create_time', $time_begin, $time_end);
                }
            })
            ->field('id,d_id,from,driver,money,state,create_time,name,phone')
            ->order('create_time desc')
            ->paginate($size, false, ['page' => $page])->toArray();
        return $list;
    }
    public static function CMSInsuranceOrders($page, $size, $time_begin, $time_end)
    {
        $list = self::where('state',OrderEnum::ORDER_COMPLETE)
            ->where(function ($query) use ($time_begin, $time_end) {
                if (strlen($time_begin) && strlen($time_end)) {
                    $query->whereBetweenTime('create_time', $time_begin, $time_end);
                }
            })
            ->field('id,d_id,from,driver,money,state,create_time,name')
            ->order('create_time desc')
            ->paginate($size, false, ['page' => $page])->toArray();
        return $list;
    }

    public static function orderCount($driver, $time_begin, $time_end)
    {
        $counts = $list = self::where('state', OrderEnum::ORDER_COMPLETE)
            ->where(function ($query) use ($driver) {
                if (strlen($driver)) {
                    $query->where('driver', 'like', '%' . $driver . '%');
                }
            })
            ->where(function ($query) use ($time_begin, $time_end) {
                if (strlen($time_begin) && strlen($time_end)) {
                    $query->whereBetweenTime('create_time', $time_begin, $time_end);
                }
            })
            ->count('phone');

        return $counts;


    }

    public static function ordersMoney($driver, $time_begin, $time_end)
    {
        $money = $list = self::where('state', OrderEnum::ORDER_COMPLETE)
            ->where(function ($query) use ($driver) {
                if (strlen($driver)) {
                    $query->where('driver', 'like', '%' . $driver . '%');
                }
            })
            ->where(function ($query) use ($time_begin, $time_end) {
                if (strlen($time_begin) && strlen($time_end)) {
                    $query->whereBetweenTime('create_time', $time_begin, $time_end);
                }
            })
            ->field('sum(money+ticket_money) as all_money,sum(ticket_money) as ticket_money ')
            ->find();

        return $money;

    }

    public static function members($driver, $time_begin, $time_end)
    {
        $members = $list = self::where('state', OrderEnum::ORDER_COMPLETE)
            ->where(function ($query) use ($driver) {
                if (strlen($driver)) {
                    $query->where('driver', 'like', '%' . $driver . '%');
                }
            })
            ->where(function ($query) use ($time_begin, $time_end) {
                if (strlen($time_begin) && strlen($time_end)) {
                    $query->whereBetweenTime('create_time', $time_begin, $time_end);
                }
            })
            ->group('phone')
            ->count('phone');

        return $members;

    }


    public static function currentOrders($page, $size)
    {
        $list = self::whereIn('state', OrderEnum::ORDER_NO . "," . OrderEnum::ORDER_ING)
            ->field('id,d_id,superior_id,null as superior,2 as transfer ,from,state,start,end,name,money,cancel_type,cancel_remark,create_time')
            ->order('create_time desc')
            ->paginate($size, false, ['page' => $page])->toArray();
        return $list;

    }


    public static function managerOrders($page, $size, $driver, $time_begin, $time_end)
    {
        $list = self::whereIn('state', '4,5')
            ->where(function ($query) use ($driver) {
                if (strlen($driver)) {
                    $query->where('driver', 'like', '%' . $driver . '%');
                }
            })
            ->where(function ($query) use ($time_begin, $time_end) {
                if (strlen($time_begin) && strlen($time_end)) {
                    $query->whereBetweenTime('create_time', $time_begin, $time_end);
                }
            })
            ->field('id,d_id,superior_id,null as superior,2 as transfer ,from,state,start,end,name,money,cancel_type,cancel_remark,create_time')
            ->order('create_time desc')
            ->paginate($size, false, ['page' => $page])->toArray();
        return $list;
    }



}