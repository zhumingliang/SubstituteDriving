<?php


namespace app\api\service;


use EasyWeChat\Factory;

class WeixinService
{
    public $app = null;

    public function __construct()
    {
        $config = [
            'app_id' => 'wxda0c645fd4945a61',
            'secret' => 'eff35b3ff1f083e1e7e51045cfbf4a40',
            'token' => 'tonglingok',
            'response_type' => 'array',
            //...
        ];

        $this->app = Factory::officialAccount($config);

    }

    public function validate()
    {
        $response = $this->app->server->serve();
        $response->send();
        exit;

    }


}