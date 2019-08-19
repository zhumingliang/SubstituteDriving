<?php


namespace app\api\service;


use app\api\model\SendMessageT;
use app\lib\enum\CommonEnum;
use app\lib\exception\SaveException;
use think\facade\Request;
use zml\tp_aliyun\SendSms;
use zml\tp_tools\Redis;
use function GuzzleHttp\Promise\each_limit;

class SendSMSService
{
    public function sendCode($phone, $type, $num = 1)
    {
        $code = rand(10000, 99999);
        $res = SendSms::instance()->send($phone, ['code' => $code], $type);

        if (key_exists('Code', $res) && $res['Code'] == 'OK') {
            $redis = new Redis();
            $token = Request::header('token');
            $redis->set($token, $phone . '-' . $code, 60);
            return true;
        }
    }

    public function sendOrderSMS($phone, $params, $num = 1)
    {

        $res = SendSms::instance()->send($phone, $params, 'driver');
        if (key_exists('Code', $res) && $res['Code'] == 'OK') {
            return true;
        }
        $this->saveSend($phone, $params, 'driver');
    }


    public function sendRechargeSMS($phone, $params, $num = 1)
    {

        $res = SendSms::instance()->send($phone, $params, 'recharge');
        if (key_exists('Code', $res) && $res['Code'] == 'OK') {
            return true;
        }
        $this->saveSend($phone, $params, 'recharge');

    }

    private function saveSend($phone, $params, $type)
    {
        $data = [
            'phone' => $phone,
            'params' => $params,
            'type' => $type,
            'failCount' => 0
        ];
        Redis::instance()->lPush('send_message', json_encode($data));
    }

    public function sendHandel()
    {
        $redis = new Redis();
        $lenth = $redis->llen('send_message');
        if (!$lenth) {
            return true;
        }
        for ($i = 0; $i < 10; $i++) {
            $data = $redis->rPop('send_message');//从结尾处弹出一个值,超时时间为60s
            $data_arr = json_decode($data, true);
            $res = SendSms::instance()->send($data_arr['phone'], $data_arr['params'], $data_arr['type']);

            $data = [
                'phone' => $data_arr['phone'],
                'params' => $data_arr['params'],
                'type' => $data_arr['type'],
                'failCount' => $data_arr['failCount'] + 1
            ];
            if (key_exists('Code', $res) && $res['Code'] == 'OK') {
                $redis->lPush('send_message_success', json_encode($data));
            } else {
                if ($data_arr['failCount'] == 2) {
                    $data['failMsg'] = json_encode($res);
                    $redis->lPush('send_message_fail', json_encode($data));
                }
                $redis->lPush('send_message', json_encode($data));

            }
            usleep(500000);//微秒，调用第三方接口，需要注意频率，
        }

    }


}