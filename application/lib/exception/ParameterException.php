<?php
/**
 * Created by PhpStorm.
 * User: zhumingliang
 * Date: 2018/3/20
 * Time: 下午1:26
 */

namespace app\lib\exception;


class ParameterException extends BaseException
{
    public $msg = '参数错误';
    public $errorCode = 10000;

}