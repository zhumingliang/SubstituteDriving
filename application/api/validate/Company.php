<?php


namespace app\api\validate;


class Company extends BaseValidate
{
    protected $rule = [
        'company' => 'require|isNotEmpty',
        'name' => 'require|isNotEmpty',
        'phone' => 'require|isNotEmpty',
        'province' => 'require|isNotEmpty',
        'city' => 'require|isMobile',
    ];

    protected $scene = [
        'save' => ['name', 'phone', 'province', 'city'],
        'save' => ['username', 'account', 'phone', 'pwd'],
        'handel' => ['d_id', 'state'],
        'online' => ['online'],
    ];
}