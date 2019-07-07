<?php


namespace app\api\validate;


class SendSMS extends BaseValidate
{
    protected $rule = [
        'phone' => 'require|isMobile'

    ];

    protected $scene = [
        'sendCodeToMINI' => ['phone'],
        'sendCodeToAndroid' => ['phone'],
    ];

}