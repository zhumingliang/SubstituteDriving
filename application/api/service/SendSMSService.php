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
       /* $params = ['code' => $code];
        $this->saveSend($phone, $params, $type);*/
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
        SendMessageT::create([
            'phone' => $phone,
            'params' => json_encode($params),
            'state' => CommonEnum::STATE_IS_FAIL,
            'type' => $type

        ]);
    }

    public function sendHandel()
    {
        $list = SendMessageT::where('state', CommonEnum::STATE_IS_FAIL)
            ->where('count', '<', 4)
            ->select()->toArray();
        if (!empty($list)) {
            foreach ($list as $k => $v) {
                $res = SendSms::instance()->send($v['phone'], json_decode($v['params'], true), $v['type']);
                if (key_exists('Code', $res) && $res['Code'] == 'OK') {
                    SendMessageT::update(['state' => CommonEnum::STATE_IS_OK], ['id' => $v['id']]);
                    continue;
                }
                SendMessageT::update(['count' => $v['count'] + 1], ['id' => $v['id']]);
            }
        }

    }


}