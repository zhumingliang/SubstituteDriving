<?php


namespace app\api\validate;


class User extends BaseValidate
{
    protected $rule = [
        'iv' => 'require|isNotEmpty',
        'encryptedData' => 'require|isNotEmpty',
        'phone' => 'require|isMobile',
        'code' => 'require|isNotEmpty'
    ];

    protected $scene = [
        'userInfo' => ['iv', 'encryptedData'],
        'bindPhone' => ['phone', 'code'],
    ];

}