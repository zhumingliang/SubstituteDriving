<?php


namespace app\api\service;


use GatewayClient\Gateway;

class GatewayService
{

    public static function sendToDriverClient($u_id, $message)
    {
        Gateway::sendToUid('driver' . '-' . $u_id, self::prefixMessage($message));
    }

    public static function sendToMiniClient($u_id, $message)
    {
        Gateway::sendToUid('mini' . '-' . $u_id, self::prefixMessage($message));
    }

    public static function isDriverUidOnline($u_id)
    {
        return Gateway::isUidOnline('driver' . '-' . $u_id);

    }

    private static function prefixMessage($message)
    {
        $data = [
            'errorCode' => 0,
            'msg' => 'success',
            'data' => $message

        ];
        return json_encode($data);

    }

}