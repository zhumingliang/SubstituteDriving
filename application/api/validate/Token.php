<?php


namespace app\api\validate;


class Token extends BaseValidate
{
    protected $rule = [
        'account' => 'require|isNotEmpty',
        'pwd' => 'require|isNotEmpty',
        'type' => 'require|in:driver,manager',
        'code' => 'require|isNotEmpty',
        'username' => 'require|isNotEmpty',
        'grade' => 'require|in:2,3,4,5',
        'state' => 'require|in:1,2',
        'belong_ids' => 'require|isNotEmpty',
    ];

    protected $scene = [
        'getAdminToken' => ['account', 'pwd'],
        'getAndroidToken' => ['account', 'pwd', 'code','type'],
        'getMINIToken' => ['code'],
        'handel' => ['id', 'state'],
        'distribution' => ['id', 'belong_ids'],
        'distributionHandel' => ['id', 'state'],
    ];

}