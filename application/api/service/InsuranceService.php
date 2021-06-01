<?php


namespace app\api\service;


use app\api\model\InsuranceLogT;
use app\lib\Http;

class InsuranceService
{
    private $company = "铜陵盟蚁网络科技有限公司";

    public function submit($orderId, $userPhone, $jieTime, $siji, $sijiPhone, $start)
    {
        $url = "http://zong.13370531053.vip/api/api/orderUpOne";
        $data = [
            'order_id' => $orderId,
            'user' => $userPhone,
            'jie_time' => $jieTime,
            'siji' => $siji,
            'siji_phone' => $sijiPhone,
            'start' => $start,
            'company' => $this->company,

        ];
        $res = Http::sendRequest($url, $data);
        //保险生成失败
        InsuranceLogT::create([
            'order_id' => $orderId,
            'data' => json_encode($data),
            'return_data' => json_encode($res),
            'type' => "begin"
        ]);
        if (empty($res['info']['code']) || $res['info']['code'] != 200) {
            return 0;
        }
        $insuranceId = $res['info']['data']['id'];
        return $insuranceId;

    }

    public function complete($insuranceId, $jvli, $order_finish, $end)
    {
        $url = "http://zong.13370531053.vip/api/api/orderSuc";
        $data = [
            'id' => $insuranceId,
            'jvli' => $jvli,
            'order_finish' => $order_finish,
            'end' => $end

        ];
        $res = Http::sendRequest($url, $data);
        InsuranceLogT::create([
            'order_id' => $insuranceId,
            'data' => json_encode($data),
            'return_data' => json_encode($res),
            'type' => "complete"
        ]);
        if (empty($res['info']['code']) || $res['info']['code'] != 200) {
            return false;
        }
        return true;
    }

    public function search($order_id)
    {
        $url = "http://zong.13370531053.vip/api/api/search";
        $data = [
            'order_id' => $order_id,
            'company' => $this->company
        ];
        $res = Http::sendRequest($url, $data);
        if (empty($res['info']['code']) || $res['info']['code'] == 202 || $res['info']['code'] == 402) {
            //保险生成失败
            InsuranceLogT::create([
                'order_id' => $order_id,
                'data' => json_encode($data),
                'return_data' => json_encode($res),
                'type' => "search"
            ]);
            return false;
        }

        if ($res['info']['code'] == 200) {
            //订单已生成,但未结束
            echo "no_complete";

        } else if ($res['info']['code'] == 201) {
            // 该订单已完成
            echo "complete";
        }
        return true;
    }

}