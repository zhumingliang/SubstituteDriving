<?php
/**
 * Created by PhpStorm.
 * User: mingliang
 * Date: 2018/10/30
 * Time: 1:56 AM
 */

namespace app\lib\exception;


class AdminException extends BaseException
{
    public $code = 200;
    public $msg = '新增小区账户失败';
    public $errorCode = 240001;

}