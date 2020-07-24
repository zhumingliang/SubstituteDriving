<?php
/**
 * Created by PhpStorm.
 * User: mingliang
 * Date: 2018/5/27
 * Time: 下午4:06
 */

namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class AdminT extends Model
{
    public function company()
    {
        return $this->belongsTo('CompanyT', 'company_id', 'id');
    }

    public static function admin($account)
    {
        $admin = AdminT::where('account', '=', $account)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->with('company')
            ->find();
        return $admin;
    }
}