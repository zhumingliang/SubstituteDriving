<?php

namespace app\api\controller\v1;

use app\lib\exception\SuccessMessage;
use app\lib\exception\SuccessMessageWithData;
use GatewayClient\Gateway;

class Index
{
    public function index()
    {
        return json(new SuccessMessage([
            'data' => [
                'name' => 'zml'
            ]
        ]));
    }

    public function send($client_id)
    {
        //  var_dump(Gateway::sendToClient($client_id, 1));
    }

}
