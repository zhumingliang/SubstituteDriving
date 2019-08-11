<?php


namespace app\api\service;


use EasyWeChat\Factory;

class WeixinService
{
    public $app = null;

    public function __construct()
    {
      /*  $config = [
            'app_id' => 'wxda0c645fd4945a61',
            'secret' => 'eff35b3ff1f083e1e7e51045cfbf4a40',
            'token' => 'tonglingok',
            'response_type' => 'array',
            //23xAerLQPXzNWHFRZQROvv4u7AHAmzPMa5ZzlRo3FnU
        ];

        $this->app = Factory::officialAccount($config);*/
    }

    public function validate()
    {
        $config = [
            'app_id' => 'wxda0c645fd4945a61',
            'secret' => 'eff35b3ff1f083e1e7e51045cfbf4a40',
            'token' => 'tonglingok',
            'response_type' => 'array',
            //23xAerLQPXzNWHFRZQROvv4u7AHAmzPMa5ZzlRo3FnU
        ];

        $this->app = Factory::officialAccount($config);
        $response = $this->app->server->serve();
        $response->send();
        exit;

    }


}