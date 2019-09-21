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

    public static function onlineDrivers()
    {
        $count = 0;
        $list = Gateway::getAllUidList();
        if (empty($list)) {
            return $count;
        }
        foreach ($list as $k => $v) {
            if (substr($v, 0, 1) == 'd') {
                $count++;
            }
        }
        return $count;
    }

    public static function isMINIUidOnline($u_id)
    {
        return Gateway::isUidOnline('mini' . '-' . $u_id);

    }

    private static function prefixMessage($message)
    {
        $data = [
            'errorCode' => 0,
            'msg' => 'success',
            'type' => $message['type'],
            'data' => $message['order_info']

        ];
        return json_encode($data);

    }

}