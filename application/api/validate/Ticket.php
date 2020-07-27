<?php


namespace app\api\validate;


class Ticket extends BaseValidate
{
    protected $rule = [
        'id' => 'require|isPositiveInteger',
        'name' => 'require|isNotEmpty',
        'price' => 'require',
        'time_begin' => 'require|isNotEmpty',
        'time_end' => 'require|isNotEmpty',
        'count' => 'require|isPositiveInteger',
        'scene' => 'require|in:1,2,3',
        'source' => 'require|in:1,2',
        'state' => 'require|in:1,2,3',
        'u_id' => 'require|isNotEmpty',
        'phone' => 'require|isNotEmpty',
        't_id' => 'require|isPositiveInteger'
    ];

    protected $scene = [
        'save' => ['name', 'price', 'time_begin', 'time_end', 'count', 'scene'],
        'handel' => ['id', 'state'],
        'update' => ['id'],
        'send' => ['phone', 't_id'],
    ];

}