<?php


namespace app\api\validate;


class Admin extends BaseValidate
{
    protected $rule = [
        'id' => 'require',
        'phone' => 'require|isMobile',
        'pwd' => 'require|isNotEmpty',
        'account' => 'require|isNotEmpty',
        'username' => 'require|isNotEmpty',
        'grade' => 'require|in:2,3,4,5',
        'state' => 'require|in:1,2',
        'belong_ids' => 'require|isNotEmpty',
    ];

    protected $scene = [
        'save' => ['phone', 'pwd', 'account', 'username', 'grade'],
        'handel' => ['id', 'state'],
        'distribution' => ['id', 'belong_ids'],
        'distributionHandel' => ['id', 'state'],
    ];

}