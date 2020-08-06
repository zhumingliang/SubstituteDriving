<?php


namespace app\api\service;


use app\api\model\CompanyT;
use app\api\model\RechargeT;
use app\api\model\RechargeTemplateT;
use app\api\model\SendMessageT;
use app\api\model\SmsRechargeT;
use app\api\model\SmsRecordT;
use app\lib\enum\CommonEnum;
use app\lib\enum\UserEnum;
use app\lib\exception\ParameterException;
use app\lib\exception\SaveException;
use app\lib\exception\UpdateException;
use app\lib\Http;
use think\Exception;
use think\facade\Request;
use think\Queue;
use zml\tp_aliyun\SendSms;
use zml\tp_tools\Redis;

class SendSMSService
{


    public function getSign()
    {
        $company_id = Token::getCurrentTokenVar('company_id');
        $company = CompanyT::where('id', $company_id)->find();
        return $company->sign;

    }

    public function sendCode($phone, $type, $num = 1)
    {
        $code = rand(10000, 99999);
        $params = ['code' => $code];
        $this->sendSms($phone, 'drive_' . $type, $params);
        $token = Request::header('token');
        $redis = new Redis();
        $redis->set($token, $phone . '-' . $code, 120);
        return true;
    }

    public function sendOrderSMS($phone, $params, $num = 1)
    {
        $this->sendSms($phone, 'drive_receive', $params);
    }


    public function sendRechargeSMS($phone, $params, $num = 1)
    {
        $this->sendSms($phone, 'drive_recharge', $params);
    }

    public function sendDriveCreateOrderSMS($phone, $params, $num = 1)
    {
        $this->sendSms($phone, 'drive_create_order', $params);

    }

    public function sendOrderCompleteSMS($phone, $params, $num = 1)
    {
        $this->sendSms($phone, 'drive_order_complete', $params);
    }

    public function sendTicketSMS($phone, $params, $num = 1)
    {
        $this->sendSms($phone, 'drive_ticket', $params);
    }

    public function sendMINISMS($phone, $params = [], $num = 1)
    {
        $this->sendSms($phone, 'drive_mini', $params);

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

    public function sendSms($phone_number, $type, $params)
    {
        $url = 'http://service.tonglingok.com/sms/template';
        $data = [
            'phone_number' => $phone_number,
            "type" => $type,
            "sign" => $this->getSign(),
            "params" => empty($params) ? ['create_time' => date('Y-m-d H:i:s')] : $params
        ];
        $res = Http::sendRequest($url, $data);
        if ($res['ret'] !== true || $res['info']['errorCode'] !== 0) {
            throw new SaveException(['msg' => '发送验证码失败']);
        }

    }

    public function records($params)
    {
        $grade = Token::getCurrentTokenVar('grade');
        if ($grade == UserEnum::USER_GRADE_VILLAGE) {
            $sign = $params['sign'];
        } else {
            $sign = Token::getCurrentTokenVar('sign');
        }
        $records = SmsRecordT::getList($sign, $params['phone'], $params['state'], $params['time_begin'], $params['time_end'], $params['page'], $params['size']);
        return $records;
    }

    public function statistic($sign)
    {
        $grade = Token::getCurrentTokenVar('grade');
        if ($grade != UserEnum::USER_GRADE_VILLAGE) {
            $sign = Token::getCurrentTokenVar('sign');
        }
        $successCount = $failCount = 0;
        $sendCount = SmsRecordT::sendCount($sign);
        foreach ($sendCount as $k => $v) {
            if ($v['state'] == CommonEnum::STATE_IS_OK) {
                $successCount = $v['count'];
            } else if ($v['state'] == CommonEnum::STATE_IS_FAIL) {
                $failCount = $v['count'];
            }
        }
        $rechargeCount = SmsRechargeT::rechargeCount($sign);
        return [
            'sendAll' => $successCount + $failCount,
            'success' => $successCount,
            'fail' => $failCount,
            'balance' => $rechargeCount - $successCount - $failCount
        ];
    }

    public function managerRecharge($params)
    {
        $params['state'] = CommonEnum::STATE_IS_OK;
        $params['pay'] = CommonEnum::STATE_IS_OK;
        $params['order_number'] = makeOrderNo();
        $params['company'] = (new CompanyService())->getCompanyName($params['sign']);
        $recharge = SmsRechargeT::create($params);
        if (!$recharge) {
            throw new SaveException();
        }
    }

    public function saveRechargeTemplate($params)
    {
        $params['state'] = CommonEnum::STATE_IS_OK;
        $recharge = RechargeTemplateT::create($params);
        if (!$recharge) {
            throw new SaveException();
        }
    }

    public function updateRechargeTemplate($params)
    {
        $recharge = RechargeTemplateT::update($params);
        if (!$recharge) {
            throw new UpdateException();
        }
    }

    public function rechargeTemplates($page, $size)
    {
        $templates = RechargeTemplateT::templates($page, $size);
        return $templates;
    }

    public function agentRecharge($template_id)
    {
        $template = RechargeTemplateT::template($template_id);
        if (empty($template)) {
            throw new ParameterException(['msg' => '参数错误，充值模板不存在']);
        }
        $data = [
            'count' => $template->count,
            'money' => $template->money,
            'state' => CommonEnum::STATE_IS_OK,
            'template_id' => $template_id,
            'company' => 1,//Token::getCurrentTokenVar('company'),
            'status' => 'paid_fail',
            'order_number' => makeOrderNo(),
            'sign' => 'ok',//Token::getCurrentTokenVar('sign'),
            'type' => 1
        ];
        $recharge = SmsRechargeT::create($data);
        if (!$recharge) {
            throw new SaveException();
        }
        //发起支付请求
        $data = $this->getPayUrl($recharge->id);
        $url = (new QrcodeService())->qr_code($data['url']);
        return config('setting.domain') . $url;

    }


    public function getPayUrl($order_id)
    {
        $url = 'http://service.tonglingok.com/pay/unifiedOrder';
        $data = [
            'id' => $order_id,
            'pay_type' => 'weixin'
        ];
        $res = Http::sendRequest($url, $data);
        if ($res['ret'] !== true || $res['info']['errorCode'] !== 0) {
            throw new SaveException(['msg' => '获取支付参数失败']);
        }

        return $res['info']['data'];

    }

    public function rechargeTemplate($id)
    {
        return RechargeTemplateT::template($id);
    }

    public function recharges($sign, $page, $size, $time_begin, $time_end)
    {
        if (Token::getCurrentTokenVar('grade') == UserEnum::USER_GRADE_ADMIN) {
            $sign = Token::getCurrentTokenVar('sign');
        }
        $list = SmsRechargeT::recharges($sign, $page, $size, $time_begin, $time_end);
        return $list;
    }

}