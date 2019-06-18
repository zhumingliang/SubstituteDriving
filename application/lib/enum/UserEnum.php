<?php
/**
 * Created by PhpStorm.
 * User: zhumingliang
 * Date: 2018/4/16
 * Time: 上午9:56
 */

namespace app\lib\enum;


class UserEnum
{

    //管理员
    const USER_GRADE_ADMIN = 1;

    //加盟商
    const USER_GRADE_JOIN = 2;

    //小区管理员
    const USER_GRADE_VILLAGE = 3;


    //账号正常
    const USER_STATE_OK = 1;

    //账号停用
    const USER_STATE_STOP = 2;

    //小程序普通用户
    const USER_MINI_NORMAL = 1;

    //小程序店铺
    const USER_MINI_SHOP = 2;

    //小程序小区管理员
    const USER_MINI_VILLAGE = 3;


}