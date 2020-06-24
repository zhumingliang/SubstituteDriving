<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\OrderService;
use app\api\service\TaskService;
use app\lib\exception\SaveException;
use app\lib\exception\SuccessMessage;
use think\facade\Request;
use think\Queue;

class Service extends BaseController
{
    public function orderHandel()
    {
        //  (new OrderService())->handelMiniNoAnswer();
        //   (new OrderService())->handelDriverNoAnswer();
        //   (new OrderService())->orderListHandel();
        //  (new SendSMS())->sendHandel();

    }

    public function failHandel()
    {
        // (new SendSMS())->sendHandel();
        // (new OrderService())->handelDriverNoAnswer();
    }

    public function sendOrderNoticeToDriver()
    {
        $params = Request::param();
        (new TaskService())->sendToDriverTask($params);
        return json(new SuccessMessage());


    }

}