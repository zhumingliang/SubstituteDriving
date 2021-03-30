<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class StartPriceT extends Model
{
    public static function companyPrices($companyId)
    {
        return self::where('company_id', $companyId)
            ->where('type', 1)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->order('order')
            ->select();
    }

}