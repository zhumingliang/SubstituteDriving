<?php


namespace app\api\validate;


class Shop extends BaseValidate
{
    protected $rule = [
        'id' => 'require|isPositiveInteger',
        'market' => 'require|isNotEmpty',
        'name' => 'require|isNotEmpty',
        'code' => 'require',
        'token' => 'require',
        'state' => 'require|in:1,2',
        'remark' => 'require',
    ];

    protected $scene = [
        'save' => ['market', 'name', 'code', 'token', 'state'],
        'update' => ['id'],
        'handel' => ['id', 'state'],
        'distribution' => ['id', 'belong_ids'],
        'distributionHandel' => ['id', 'state'],
    ];
}