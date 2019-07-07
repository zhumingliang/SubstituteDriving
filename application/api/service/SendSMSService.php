<?php


namespace app\api\service;


use think\facade\Cache;
use zml\tp_aliyun\SendSms;

class SendSMSService
{
    public function sendCode($phone, $type)
    {
        $code = rand(10000, 99999);
        $res = SendSms::instance()->send($phone, $code, $type);
        if (key_exists('Code', $res) && $res['Code'] == 'OK') {
            Cache::remember($type . '_code', $code, 60 * 2);
            return true;
        }
        $this->sendCode($phone, $type);

    }


}