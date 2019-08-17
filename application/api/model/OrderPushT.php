<?php


namespace app\api\model;


use think\Model;

class OrderPushT extends Model
{
    public function driver()
    {
        return $this->belongsTo('DriverT', 'd_id', 'id');

    }

}