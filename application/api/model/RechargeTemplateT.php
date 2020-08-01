<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class RechargeTemplateT extends Model
{
    protected $connection = 'db_service';

    public static function templates($page, $size)
    {
        return self::where('state', CommonEnum::STATE_IS_OK)
            ->order('create_time desc')
            ->paginate($size, false, ['page' => $page])->toArray();
    }

    public static function template($id)
    {
        return self::where('id', $id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->find();
    }
}