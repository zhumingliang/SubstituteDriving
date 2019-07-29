<?php


namespace app\api\model;


use think\Model;

class UserV extends Model
{
    public function getSourceAttr($value)
    {
        $data = [1 => "小程序用户", 2 => "微信公众号用户", 3 => "司机添加", 4 => "管理员添加"];
        return $data[$value];
    }

    public static function users($page, $size, $name, $time_begin, $time_end, $phone, $money_min, $money_max, $count_min, $count_max)
    {
        $list = self::where(function ($query) use ($name) {
            if (strlen($name)) {
                $query->where('nickName', 'like', '%' . $name . '%');
            }
        })
            ->where(function ($query) use ($phone) {
                if (strlen($phone)) {
                    $query->where('phone', '=', $phone);
                }
            })
            ->where(function ($query) use ($money_min, $money_max) {
                if ($money_min || $money_max) {
                    $query->whereBetween('money', $money_min . ',' . $money_max);
                }
            })
            ->where(function ($query) use ($count_min, $count_max) {
                if ($count_min || $count_max) {
                    $query->whereBetween('count', $count_min . ',' . $count_max);
                }
            })
            ->where(function ($query) use ($time_begin, $time_end) {
                if (strlen($time_begin) && strlen($time_end)) {
                    $query->whereBetweenTime('create_time', $time_begin, $time_end);
                }
            })
            ->order('create_time desc')
            ->paginate($size, false, ['page' => $page])->toArray();
        return $list;

    }

}