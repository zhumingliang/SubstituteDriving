<?php


namespace app\api\model;


use think\Model;

class HouseImageT extends BaseModel
{
    public function getUrlAttr($value)
    {
        return $this->prefixImgUrl($value);

    }


}