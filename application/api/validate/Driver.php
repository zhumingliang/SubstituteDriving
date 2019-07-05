<?php


namespace app\api\validate;


class Driver extends BaseValidate
{
    protected $rule = [
        'client_id' => 'require|isNotEmpty',
        'username'=>'require|isNotEmpty',
        'account'=>'require|isNotEmpty',
        'phone'=>'require|isMobile',
        'pwd'=>'require|isNotEmpty',
        'd_id'=>'require|isPositiveInteger',
        'state'=>'require|in:1,2',

    ];

    protected $scene = [
        'bind' => ['client_id'],
        'save' => ['username','account','phone','pwd'],
        'handel' => ['d_id','state'],
    ];

}