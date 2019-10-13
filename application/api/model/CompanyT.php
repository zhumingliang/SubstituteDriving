<?php


namespace app\api\model;


use think\Model;

class CompanyT extends Model
{
    public static function agents($page, $size, $phone, $company, $username)
    {
        return self::where(function ($query) use ($phone) {
            if (!empty($phone)) {
                $query->where('phone', $phone);
            }
        })
            ->where(function ($query) use ($company) {
                if (!empty($company)) {
                    $query->where('company', 'like', '%' . $company . '%');
                }
            })->where(function ($query) use ($username) {
                if (!empty($username)) {
                    $query->where('username', 'like', '%' . $username . '%');
                }
            })
            ->hidden(['update_time', 'area'])
            ->order('create_time desc')
            ->paginate($size, false, ['page' => $page])->toArray();

    }

}