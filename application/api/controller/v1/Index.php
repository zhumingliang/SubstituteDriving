<?php

namespace app\api\controller\v1;

use app\api\service\SendSMSService;

class Index
{
    public function index()
    {
        (new SendSMSService())->sendOrderSMS('18956225230', ['code' => '*****' . substr('sajdlkjdsk21312', 5), 'order_time' => date('H:i', time())]);

    }

    public function send($client_id)
    {
        //  var_dump(Gateway::sendToClient($client_id, 1));
    }

}
