<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;

class DriverT extends BaseModel
{
    public function company()
    {
        return $this->belongsTo('CompanyT', 'company_id', 'id');
    }

    public static function driver($account)
    {
        $driver = self:: where('account', '=', $account)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->with('company')
            ->find();
        return $driver;
    }
}