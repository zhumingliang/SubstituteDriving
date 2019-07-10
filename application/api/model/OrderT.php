<?php


namespace app\api\model;


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

}