<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use app\lib\enum\OrderEnum;
use think\Model;

class OrderT extends Model
{
    public function user()
    {
        return $this->belongsTo('UserT', 'u_id', 'id');

    }

    public function getOrder($o_id)
    {
        $order = self::where('id', $o_id)
            ->with('user')
            ->find();
        return $order;
    }

    public static function miniOrders($u_id, $page, $size)
    {
        $list = self::where('u_id', $u_id)
            ->where('state', '<', OrderEnum::ORDER_CANCEL)
            ->field('id,start,end,state,create_time')
            ->order('create_time desc')
            ->paginate($size, false, ['page' => $page]);
        return $list;
    }

}