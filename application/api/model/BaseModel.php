<?php
/**
 * Created by PhpStorm.
 * User: mingliang
 * Date: 2018/9/30
 * Time: 下午10:53
 */

namespace app\api\model;


use think\Model;

class BaseModel extends Model
{
    protected function prefixImgUrl($value)
    {
        $finalUrl = config('setting.img_prefix') . $value;
        return $finalUrl;
    }

}