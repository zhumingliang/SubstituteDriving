<?php

namespace app\api\controller\v1;

use GatewayClient\Gateway;

class Index
{
    public function index()
    {
        echo 111;
    }

    public function send($client_id)
    {
        var_dump(Gateway::sendToClient($client_id, 1));
    }

}
