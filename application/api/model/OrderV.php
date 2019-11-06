<?php


namespace app\api\model;


use app\api\service\Token;
use app\lib\enum\CommonEnum;
use app\lib\enum\OrderEnum;
use think\Model;

class OrderV extends Model
{
    public function getFromAttr($value)
    {
        if ($value) {
            $data = [1 => '小程序下单', 2 => '司机自主建单', 3 => '管理员自主建单', 4 => '公众号下单'];
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

    public static function CMSManagerOrders($page, $size, $driver, $time_begin, $time_end, $order_state, $order_from, $company_id = 1)
    {
        $list = self::where(function ($query) use ($company_id) {
            if (!empty($company_id)) {
                $query->where('company_id', $company_id);
            }
        })->
        where(function ($query) use ($order_state) {
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
                    $time_end = addDay(1, $time_end);
                    $query->whereBetweenTime('create_time', $time_begin, $time_end);
                }
            })
            ->where(function ($query) {
                if (Token::getCurrentTokenVar('grade') == 'insurance') {
                    $query->whereNotIn('d_id', '28,35');
                }
            })
            ->field('id,d_id,from,driver,money,state,create_time,name,phone')
            ->order('create_time desc')
            ->paginate($size, false, ['page' => $page])->toArray();
        return $list;
    }

    public static function CMSInsuranceOrders($page, $size, $time_begin, $time_end)
    {
        $list = self::where('state', OrderEnum::ORDER_COMPLETE)
            ->where(function ($query) use ($time_begin, $time_end) {
                if (strlen($time_begin) && strlen($time_end)) {
                    $time_end = addDay(1, $time_end);
                    $query->whereBetweenTime('create_time', $time_begin, $time_end);
                }
            })
            ->whereNotIn('d_id', '28,35')
            ->field('id,d_id,from,driver,money,state,create_time,name')
            ->order('create_time desc')
            ->paginate($size, false, ['page' => $page])->toArray();
        return $list;
    }

    public static function orderCount($company_id, $driver, $time_begin, $time_end)
    {
        $counts = $list = self::where(function ($query) use ($company_id) {
            if (!empty($company_id)) {
                $query->where('company_id', $company_id);
            }
        })
            ->whereNotIn('d_id', '28,35')
            ->where('state', OrderEnum::ORDER_COMPLETE)
            ->where(function ($query) use ($driver) {
                if (strlen($driver)) {
                    $query->where('driver', 'like', '%' . $driver . '%');
                }
            })
            ->where(function ($query) use ($time_begin, $time_end) {
                if (strlen($time_begin) && strlen($time_end)) {
                    $time_end = addDay(1, $time_end);
                    $query->whereBetweenTime('create_time', $time_begin, $time_end);
                }
            })
            ->count('phone');

        return $counts;


    }

    public static function ordersMoney($company_id, $driver, $time_begin, $time_end)
    {
        $money = $list = self::where(function ($query) use ($company_id) {
            if (!empty($company_id)) {
                $query->where('company_id', $company_id);
            }
        })->whereNotIn('d_id', '28,35')
            ->where('state', OrderEnum::ORDER_COMPLETE)
            ->where(function ($query) use ($driver) {
                if (strlen($driver)) {
                    $query->where('driver', 'like', '%' . $driver . '%');
                }
            })
            ->where(function ($query) use ($time_begin, $time_end) {
                if (strlen($time_begin) && strlen($time_end)) {
                    $time_end = addDay(1, $time_end);
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
                    $time_end = addDay(1, $time_end);
                    $query->whereBetweenTime('create_time', $time_begin, $time_end);
                }
            })
            ->group('phone')
            ->count('phone');

        return $members;

    }

    public static function currentOrders($company_id, $page, $size)
    {
        $list = self::where(function ($query) use ($company_id) {
            if (!empty($company_id)) {
                $query->where('company_id', $company_id);
            }
        })
            ->whereIn('state', OrderEnum::ORDER_NO . "," . OrderEnum::ORDER_ING)
            ->order('create_time desc')
            ->paginate($size, false, ['page' => $page])->toArray();
        return $list;

    }

    public static function managerOrders($company_id, $page, $size, $driver, $time_begin, $time_end, $order_state, $order_from)
    {
        $list = self::where(function ($query) use ($company_id) {
            if (!empty($company_id)) {
                $query->where('company_id', $company_id);
            }
        })->where(function ($query) use ($order_state) {
            if ($order_state < 6) {
                $query->where('state', '=', $order_state);
            }
        })->where(function ($query) use ($order_from) {
            if ($order_from < 5) {
                $query->where('from', '=', $order_from);
            }
        })->where(function ($query) use ($driver) {
            if (strlen($driver)) {
                $query->where('driver', 'like', '%' . $driver . '%');
            }
        })->where(function ($query) use ($time_begin, $time_end) {
            if (strlen($time_begin) && strlen($time_end)) {
                $time_end = addDay(1, $time_end);
                $query->whereBetweenTime('create_time', $time_begin, $time_end);
            }
        })
            ->field('id,d_id,driver,superior_id,null as superior,2 as transfer ,from,state,start,end,name,money,cancel_type,cancel_remark,create_time,phone_code,phone')
            ->order('create_time desc')
            ->paginate($size, false, ['page' => $page])->toArray();
        return $list;
    }

    public static function hotelOrders($company_id, $hotel_id, $time_begin, $time_end, $page, $size)
    {
        $time_end = addDay(1, $time_end);
        $orders = self::where('hotel_id', $hotel_id)
            ->where('company_id', $company_id)
            ->whereBetweenTime('create_time', $time_begin, $time_end)
            ->where('state', '<', 5)
            ->field('id,name,driver,phone,money,start,end,hotel,state,create_time')
            ->order('create_time desc')
            ->paginate($size, false, ['page' => $page])->toArray();
        return $orders;

    }

}