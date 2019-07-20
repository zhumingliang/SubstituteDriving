<?php


namespace app\api\service;


use app\api\model\UserV;

class UserService
{
    public function users($page, $size, $name, $time_begin, $time_end, $phone, $money_min, $money_max, $count_min, $count_max)
    {
        $users=UserV::users($page, $size, $name, $time_begin, $time_end, $phone, $money_min, $money_max, $count_min, $count_max);
        return $users;
    }


}