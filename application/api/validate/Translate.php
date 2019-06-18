<?php


namespace app\api\validate;


class Translate extends BaseValidate
{

    protected $rule = [
        'data' => 'require',
        'from' => 'require|in:zh,en,spa,fra,it,jp,pt'
    ];

    protected $scene = [
        'des' => ['from','data']
    ];

}