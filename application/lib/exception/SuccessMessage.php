<?php

namespace app\lib\exception;


class SuccessMessage extends BaseException
{
    public $msg = 'ok';
    public $errorCode = 0;
}