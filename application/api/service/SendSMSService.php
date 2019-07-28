<?php


namespace app\api\service;


use app\lib\exception\SaveException;
use think\facade\Request;
use zml\tp_aliyun\SendSms;
use zml\tp_tools\Redis;

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
            $redis = new Redis();
            $token = Request::header('token');
            $redis->set($token, $phone . '-' . $code, 60);
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



    public function sendRechargeSMS($phone, $params, $num = 1)
    {

        if ($num > 3) {
            return false;
           // throw new SaveException(['msg' => '短信服务出错']);
        }
        $res = SendSms::instance()->send($phone, $params, 'recharge');
        if (key_exists('Code', $res) && $res['Code'] == 'OK') {
            return true;
        }
        LogService::save(json_encode($res));
        $num++;
        $this->sendOrderSMS($phone, $params, $num);

    }


}