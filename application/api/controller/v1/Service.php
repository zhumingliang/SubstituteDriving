<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\OrderService;

class Service extends BaseController
{
    public function orderHandel()
    {
        (new SendSMS())->sendHandel();
        (new OrderService())->handelDriverNoAnswer();
        (new OrderService())->orderListHandel();
    }

    public function failHandel()
    {
       // (new SendSMS())->sendHandel();
       // (new OrderService())->handelDriverNoAnswer();
    }

}