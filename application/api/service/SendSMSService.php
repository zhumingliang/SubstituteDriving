<?php


namespace app\api\service;


use app\api\model\SendMessageT;
use app\lib\enum\CommonEnum;
use app\lib\exception\SaveException;
use think\Exception;
use think\facade\Request;
use think\Queue;
use zml\tp_aliyun\SendSms;
use zml\tp_tools\Redis;
use function GuzzleHttp\Promise\each_limit;

class SendSMSService
{
    public function sendCode($phone, $type, $num = 1)
    {
        $code = rand(10000, 99999);
        $params = ['code' => $code];
        $res = SendSms::instance()->send($phone, $params, $type);
        $token = Request::header('token');
        if (key_exists('Code', $res) && $res['Code'] == 'OK') {
            $redis = new Redis();
            $redis->set($token, $phone . '-' . $code, 120);
            return true;
        }
        $this->msgTask($phone, $params, $type, $token);
        //$this->saveSend($phone, $params, $type, $token);
    }

    //短信队列
    public function msgTask($phone, $params, $type, $token = '')
    {
        //php think queue:work --queue sendMsgQueue
        $jobHandlerClassName = 'app\api\job\SendMsg';//负责处理队列任务的类
        $jobQueueName = "sendDriverMsgQueue";//队列名称
        $jobData = [
            'phone' => $phone,
            'params' => $params,
            'type' => $type,
            'token' => $token
        ];;//当前任务的业务数据
        $isPushed = Queue::push($jobHandlerClassName, $jobData, $jobQueueName);
        //将该任务推送到消息队列
        if ($isPushed == false) {
            throw new SaveException(['msg' => '发送短信失败']);
        }

    }

    public function sendOrderSMS($phone, $params, $num = 1)
    {

        $res = SendSms::instance()->send($phone, $params, 'driver');
        if (key_exists('Code', $res) && $res['Code'] == 'OK') {
            return true;
        }
        $this->msgTask($phone, $params, 'driver');
        //$this->saveSend($phone, $params, 'driver');
    }


    public function sendRechargeSMS($phone, $params, $num = 1)
    {
        $res = SendSms::instance()->send($phone, $params, 'recharge');
        if (key_exists('Code', $res) && $res['Code'] == 'OK') {
            return true;
        }
        $this->msgTask($phone, $params, 'recharge');
        //$this->saveSend($phone, $params, 'recharge');
    }

    public function sendDriveCreateOrderSMS($phone, $params, $num = 1)
    {
        $res = SendSms::instance()->send($phone, $params, 'driveCreateOrder');
        if (key_exists('Code', $res) && $res['Code'] == 'OK') {
            return true;
        }
        $this->msgTask($phone, $params, 'driveCreateOrder');
    }

    public function sendOrderCompleteSMS($phone, $params, $num = 1)
    {
        $res = SendSms::instance()->send($phone, $params, 'orderComplete');
        if (key_exists('Code', $res) && $res['Code'] == 'OK') {
            return true;
        }
        $this->msgTask($phone, $params, 'orderComplete');
    }

    public function sendTicketSMS($phone, $params, $num = 1)
    {
        $res = SendSms::instance()->send($phone, $params, 'ticket');
        if (key_exists('Code', $res) && $res['Code'] == 'OK') {
            return true;
        }
        $this->msgTask($phone, $params, 'orderComplete');
    }

    public function sendMINISMS($phone, $params = '', $num = 1)
    {

        $res = SendSms::instance()->send($phone, $params, 'mini');
        if (key_exists('Code', $res) && $res['Code'] == 'OK') {
            return true;
        }
        $this->msgTask($phone, $params, 'mini');

    }

    public function saveSend($phone, $params, $type, $token = '')
    {
        $data = [
            'phone' => $phone,
            'params' => $params,
            'type' => $type,
            'token' => $token,
            'failCount' => 0
        ];
        Redis::instance()->lPush('send_message', json_encode($data));
    }

    public function sendHandel()
    {
        try {
            $redis = new Redis();
            $lenth = $redis->llen('send_message');
            if (!$lenth) {
                return true;
            }
            for ($i = 0; $i < 10; $i++) {
                $data = $redis->rPop('send_message');//从结尾处弹出一个值,超时时间为60s
                $data_arr = json_decode($data, true);
                if (empty($data_arr['phone'])) {
                    continue;
                }
                $res = SendSms::instance()->send($data_arr['phone'], $data_arr['params'], $data_arr['type']);
                $data = [
                    'phone' => $data_arr['phone'],
                    'params' => $data_arr['params'],
                    'type' => $data_arr['type'],
                    'failCount' => $data_arr['failCount'] + 1
                ];
                if (key_exists('Code', $res) && $res['Code'] == 'OK') {
                    $redis->lPush('send_message_success', json_encode($data));
                    if (!empty($data_arr['token'])) {
                        $redis = new Redis();
                        $redis->set($data_arr['token'], $data_arr['phone'] . '-' . $data_arr['params']['code'], 120);
                  }
                } else {
                    if ($data_arr['failCount'] > 2) {
                        $data['failMsg'] = json_encode($res);
                        $redis->lPush('send_message_fail', json_encode($data));

                    } else {
                        $redis->lPush('send_message', json_encode($data));
                    }
                }
                usleep(100000);//微秒，调用第三方接口，需要注意频率，
            }
        } catch (Exception $e) {
            LogService::save('sendHandel：' . $e->getMessage());
        }
    }


}