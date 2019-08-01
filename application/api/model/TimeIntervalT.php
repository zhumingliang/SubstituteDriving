<?php


namespace app\api\model;


use think\Model;

class TimeIntervalT extends Model
{

    public function getTimeBeginAttr($value){
        return date('H:i',strtotime($value));

    }

    public function getTimeEndAttr($value){
        return date('H:i',strtotime($value));
    }
}