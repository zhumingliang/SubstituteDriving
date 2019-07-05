<?php
/**
 * Created by PhpStorm.
 * User: mingliang
 * Date: 2018/11/2
 * Time: 4:14 PM
 */

namespace app\lib\exception;


class AuthException extends BaseException
{
    public $msg = '用户权限不足';
    public $errorCode = 50001;

}