<?php


namespace app\api\service;


use app\lib\exception\SaveException;
use zml\tp_aliyun\SendSms;

class SendSMSService
{
    public function sendCode($phone, $type, $num = 1)
    {

        if ($num > 3) {
            throw new SaveException(['msg' => '短信服务出错']);
        }
        $code = rand(10000, 99999);
        $res = SendSms::instance()->send($phone, ['code' => $code], $type);
        if (key_exists('Code', $res) && $res['Code'] == 'OK') {
            LogService::save($phone . '-' . $code);
            Session($type . '_code', $phone . '-' . $code);
            return true;
        }
        $num++;
        $this->sendCode($phone, $type, $num);

    }

    public function sendOrderSMS($phone, $params, $num = 1)
    {

        if ($num > 3) {
            throw new SaveException(['msg' => '短信服务出错']);
        }
        $res = SendSms::instance()->send($phone, $params, 'driver');
        if (key_exists('Code', $res) && $res['Code'] == 'OK') {
            return true;
        }
        LogService::save(json_encode($res));
        $num++;
        $this->sendOrderSMS($phone, $params, $num);

    }


}