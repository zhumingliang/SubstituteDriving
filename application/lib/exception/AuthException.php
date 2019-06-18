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
    public $code = 200;
    public $msg = '新增分组失败';
    public $errorCode = 250001;

}