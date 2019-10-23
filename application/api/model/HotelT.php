<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class HotelT extends Model
{
    public static function hotels($company_id, $page, $size)
    {
        $hotels = HotelT::where('company_id', $company_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->hidden(['state', 'update_time'])
            ->order('create_time desc')
            ->paginate($size, false, ['page' => $page]);
        return $hotels;
    }

}