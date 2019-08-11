<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\WeixinService;
use app\lib\exception\SuccessMessage;

class WeiXinPublic extends BaseController
{
    public function server()
    {
        (new WeixinService())->validate();

    }

    public function createMenu()
    {
        (new WeixinService())->createMenu();
        return json(new SuccessMessage());
    }

}