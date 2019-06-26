<?php
/**
 * Created by 七月.
 * Author: 七月
 * Date: 2017/5/22
 * Time: 10:33
 */

namespace app\lib\exception;


class WeChatException extends BaseException
{
    public $msg = '微信服务器接口调用失败';
    public $errorCode = 999;
}