<?php


namespace app\api\validate;


class Order extends BaseValidate
{
    protected $rule = [
        'p_id' => 'require|isPositiveInteger',
        'type' => 'require|in:2,3'
    ];

    protected $scene = [
        'orderPushHandel' => ['p_id', 'type']
    ];
}