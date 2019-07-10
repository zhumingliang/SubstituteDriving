<?php


namespace app\api\service;


use GatewayClient\Gateway;

class GatewayService
{
    public function sendToClient($u_id, $message)
    {
        Gateway::sendToUid($u_id, $message);
    }

}