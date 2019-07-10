<?php


namespace app\api\validate;


class Order extends BaseValidate
{
    protected $rule = [
        'p_id' => 'require|isPositiveInteger',
        'id' => 'require|isPositiveInteger',
        'type' => 'require|in:2,3',
        'start' => 'require|isNotEmpty',
        'start_lng' => 'require|isNotEmpty',
        'start_lat' => 'require|isNotEmpty',
        'end' => 'require|isNotEmpty',
        'end_lat' => 'require|isNotEmpty',
        'end_lng' => 'require|isNotEmpty',
        'remark' => 'require|isNotEmpty',
        't_id' => 'require|isPositiveInteger',
    ];

    protected $scene = [
        'orderPushHandel' => ['p_id', 'type'],
        'miniCancel' => ['id', 'remark'],
        'orderBegin' => ['id'],
        'saveMiniOrder' => ['start', 'start_lng','start_lat','end', 'end_lng','end_lat','t_id'],
    ];
}