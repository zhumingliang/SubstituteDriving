<?php


namespace app\api\service;


use GatewayClient\Gateway;

class GatewayService
{
    public static function sendToDriverClient($u_id, $message)
    {
        Gateway::sendToUid('driver' . '-' . $u_id, $message);
    }

    public static function sendToMiniClient($u_id, $message)
    {
        Gateway::sendToUid('mini' . '-' . $u_id, $message);
    }

    public static function isDriverUidOnline($u_id)
    {
        return Gateway::isUidOnline('driver' . '-' . $u_id);

    }

}