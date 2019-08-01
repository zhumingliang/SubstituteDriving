<?php


namespace app\api\validate;


class Notice extends BaseValidate
{
    protected $rule = [
        'id' => 'require|isPositiveInteger',
        'area' => 'require|isNotEmpty',
        'title' => 'require|isNotEmpty',
        'content' => 'require|isNotEmpty',
        'from' => 'require|in:android,pc',
        'state' => 'require|in:1,2,3'
    ];

    protected $scene = [
        'save' => ['area', 'title', 'content', 'from'],
        'handel' => ['id', 'state'],
        'update' => ['id']
    ];
}