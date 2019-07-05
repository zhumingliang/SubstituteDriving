<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class RechargeT extends Model
{
    public function driver()
    {
        return $this->belongsTo('DriverT', 'd_id', 'id');
    }

    public static function rechargesForManager($page, $size)
    {
        $list = self::where('state', CommonEnum::STATE_IS_OK)
            ->with(['driver' => function ($query) {
                $query->field('id,username');
            }])
            ->field('id,money,d_id,create_time')
            ->paginate($size, false, ['page' => $page]);
        return $list;

    }
}