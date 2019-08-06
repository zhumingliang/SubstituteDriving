<?php


namespace app\api\validate;


class SystemPrice extends BaseValidate
{
    protected $rule = [
        'distance' => 'require|isNotEmpty',
        'price' => 'require|isNotEmpty',
        'order' => 'require|isPositiveInteger',
        'type' => 'require|in:1,2',
        'state' => 'require|in:1,2',
        'id' => 'require|isPositiveInteger',
        'info' => 'require|isNotEmpty',
    ];

    protected $scene = [
        'startSave' => ['distance', 'price', 'order', 'type'],
        'startHandel' => ['id'],
        'startPrice' => ['type'],
        'startUpdate' => ['id'],
        'weatherUpdate' => ['id'],
        'waitUpdate' => ['id'],
        'startOpenHandel' => ['state'],
        'intervalUpdate' => ['id'],
    ];

}