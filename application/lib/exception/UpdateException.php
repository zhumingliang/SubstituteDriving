<?php
/**
 * Created by PhpStorm.
 * User: mingliang
 * Date: 2019-02-25
 * Time: 01:39
 */

namespace app\lib\exception;


class UpdateException extends BaseException
{
    public $code = 200;
    public $msg = '更新操作失败';
    public $errorCode = 40002;

}