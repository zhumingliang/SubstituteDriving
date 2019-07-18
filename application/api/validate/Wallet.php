<?php


namespace app\api\validate;


class Wallet extends BaseValidate
{
    protected $rule = [
        'd_id' => 'require|isPositiveInteger',
        'money' => 'require|isNotEmpty',
    ];

    protected $scene = [
        'driverRecharges' => ['d_id'],
        'saveRecharge' => ['d_id','money'],
    ];
}