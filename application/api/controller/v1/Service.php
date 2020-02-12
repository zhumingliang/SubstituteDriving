<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\OrderService;

class Service extends BaseController
{
    public function orderHandel()
    {
        (new OrderService())->handelMiniNoAnswer();
        (new OrderService())->handelDriverNoAnswer();
        (new OrderService())->orderListHandel();
       // (new SendSMS())->sendHandel();

    }

    public function failHandel()
    {
        // (new SendSMS())->sendHandel();
        // (new OrderService())->handelDriverNoAnswer();
    }

}