<?php


namespace app\api\validate;


class User extends BaseValidate
{
    protected $rule = [
        'iv' => 'require|isNotEmpty',
        'encryptedData' => 'require|isNotEmpty'
    ];

    protected $scene = [
        'userInfo' => ['iv', 'encryptedData'],
    ];

}