<?php


namespace app\api\validate;


class Goods extends BaseValidate
{
    protected $rule = [
        'id' => 'require',
        'g_id' => 'require',
        'type' => 'require|in:main,sku',
        'delete_type' => 'require|in:one,all',
        'image' => 'require',
    ];

    protected $scene = [
        'goodsInfo' => ['id'],
        'goodsPrice' => ['id'],
        'goodsDes' => ['id'],
        'updateInfo' => ['id'],
        'updatePrice' => ['id'],
        'updateDes' => ['g_id'],
        'deleteImage' => ['id', 'type'],
        'uploadImage' => ['id', 'type', 'image'],
        'deleteSku' => ['id', 'delete_type'],
        'deleteGoods' => ['id'],
    ];
}