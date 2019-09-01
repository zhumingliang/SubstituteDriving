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

    public static function rechargesForManager($d_id,$page, $size)
    {
        $list = self::where('state', CommonEnum::STATE_IS_OK)
            ->with(['driver' => function ($query) {
                $query->field('id,username');
            }])
            ->where('d_id',$d_id)
            ->field('id,money,d_id,create_time')
            ->order('create_time desc')
            ->paginate($size, false, ['page' => $page]);
        return $list;

    }

    public static function rechargesForDriver($page, $size, $d_id)
    {
        $list = self::where('d_id', $d_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->field('id,money,create_time')
            ->order('create_time desc')
            ->paginate($size, false, ['page' => $page]);
        return $list;

    }
}