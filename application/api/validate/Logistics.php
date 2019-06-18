<?php


namespace app\api\validate;


class Logistics extends BaseValidate
{
    protected $rule = [
        'CountryCode' => 'require|isNotEmpty',
        'weight' => 'require|isNotEmpty',
        'length' => 'require|isPositiveInteger',
        'width' => 'require|isPositiveInteger',
        'height' => 'require|isPositiveInteger',
        'PackageType' => 'require|in:1,2,3'
    ];

    protected $scene = [
        'price' => ['CountryCode', 'weight', 'length', 'width', 'width', 'PackageType']
    ];

}