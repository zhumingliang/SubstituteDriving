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

    public static function users($page, $size, $name, $time_begin, $time_end, $phone, $company_id)
    {
        $time_end=addDay(1,$time_end);
        $list = self::where(function ($query) use ($company_id) {
            if (!empty($company_id)) {
                $query->where('company_id', '=', $company_id);
            }
        })
            ->where(function ($query) use ($name) {
                if (strlen($name)) {
                    $query->where('nickName', 'like', '%' . $name . '%');
                }
            })
            ->where(function ($query) use ($phone) {
                if (strlen($phone)) {
                    $query->where('phone', '=', $phone);
                }
            })
            ->where(function ($query) use ($time_begin, $time_end) {
                if (strlen($time_begin) && strlen($time_end)) {
                    $query->whereBetweenTime('order_time', $time_begin, $time_end);
                }
            })
            ->field('id,nickName,phone,source,create_time,parent_name,company_id,sum(money) as money,count(order_id) as count,order_time')
            ->order('create_time desc')
            ->group('phone')
            ->paginate($size, false, ['page' => $page])->toArray();
        return $list;

    }

}