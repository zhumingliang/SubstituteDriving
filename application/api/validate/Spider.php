<?php


namespace app\api\validate;


class Spider extends BaseValidate
{
    protected $rule = [
        'url' => 'require',
    ];

    protected $scene = [
        'upload' => ['url'],
    ];

}