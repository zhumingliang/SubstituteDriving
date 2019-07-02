<?php

namespace app\lib\exception;


class SuccessMessageWithData extends BaseException
{
    public $msg = 'ok';
    public $errorCode = 0;
}