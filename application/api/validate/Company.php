<?php


namespace app\api\validate;


class Company extends BaseValidate
{
    protected $rule = [
        'id' => 'require|isPositiveInteger',
        'company' => 'require|isNotEmpty',
        'name' => 'require|isNotEmpty',
        'phone' => 'require|isNotEmpty',
        'province' => 'require|isNotEmpty',
        'city' => 'require|isNotEmpty',
    ];

    protected $scene = [
        'save' => ['name', 'phone', 'province', 'city'],
        'update' => ['id'],
        'handel' => ['d_id', 'state'],
        'online' => ['online'],
    ];
}