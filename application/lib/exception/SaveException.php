<?php
/**
 * Created by PhpStorm.
 * User: mingliang
 * Date: 2019-02-25
 * Time: 01:38
 */

namespace app\lib\exception;


class SaveException extends BaseException
{
    public $msg = '新增操作失败';
    public $errorCode = 40001;

}