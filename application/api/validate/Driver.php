<?php


namespace app\api\validate;


class Driver extends BaseValidate
{
    protected $rule = [
        'client_id' => 'require|isNotEmpty'

    ];

    protected $scene = [
        'bind' => ['client_id'],
    ];

}