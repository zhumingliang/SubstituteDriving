<?php


namespace app\api\model;


use think\Model;

class UserT extends Model
{
    /**
     * 根据openid获取用户数据
     */
    public static function getByOpenID($openId)
    {
        $user = self::where('openId', '=', $openId)
            ->find();
        return $user;
    }
}