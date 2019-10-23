<?php


namespace app\api\service;


use app\api\model\DriverT;
use app\api\model\UserV;
use app\lib\enum\CommonEnum;
use app\lib\exception\AuthException;
use think\facade\Cache;
use think\facade\Request;

class UserService
{
    public function users($page, $size, $name, $time_begin, $time_end, $phone, $money_min, $money_max, $count_min, $count_max)
    {
        $company_id = Token::getCurrentTokenVar('company_id');
        $users = UserV::users($page, $size, $name, $time_begin, $time_end, $phone, $money_min, $money_max, $count_min, $count_max, $company_id);
        return $users;
    }

    public function checkDriverState($driver_id)
    {
        $driver = DriverT::where('id', $driver_id)
            ->find();
        if ($driver->state != CommonEnum::STATE_IS_OK) {
            $token = Request::header('token');
            Cache::rm($token);
            throw new AuthException(['msg' => "账号状态异常，请联系管理员"]);
        }


    }


}