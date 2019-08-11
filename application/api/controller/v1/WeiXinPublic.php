<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\WeixinService;

class WeiXinPublic extends BaseController
{
    public function server()
    {
        (new WeixinService())->validate();


    }

}