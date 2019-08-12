<?php


namespace app\api\service;


use app\api\model\StartPriceT;
use app\api\model\TimeIntervalT;
use app\api\model\WaitPriceT;
use app\lib\exception\WeChatException;
use EasyWeChat\Factory;

class WeixinService
{
    public $app = null;

    public function __construct()
    {
        $config = [
            'app_id' => 'wxda0c645fd4945a61',
            'secret' => 'eff35b3ff1f083e1e7e51045cfbf4a40',
            'token' => 'tonglingok',
            'response_type' => 'array',
            //23xAerLQPXzNWHFRZQROvv4u7AHAmzPMa5ZzlRo3FnU
        ];

        $this->app = Factory::officialAccount($config);
    }

    public function validate()
    {

        /* $response = $this->app->server->serve();
         $response->send();
         exit;*/

        $this->app->server->push(function ($message) {
            $msg = "您好！欢迎使用OK代驾。";
            // $message['MsgType'] // 消息类型：event, text....
            $type = $message['MsgType'];
            if ($type == "event") {
                $event = $message['Event'];
                if ($event == "CLICK") {
                    $msg = $this->click($message['EventKey']);
                }

            }
            return $msg;
        });
        $response = $this->app->server->serve();
        $response->send();
    }


    private function click($key)
    {
        $return_msg = '您好！欢迎使用OK代驾。';
        if ($key == "fee") {
            $return_msg = $this->prefixFee();
        } else if ($key == "contact") {
            $return_msg = '服务电话：13515623335（微信同号）';

        }
        return $return_msg;

    }

    public function createMenu()
    {
        $menus = [
            [
                "name" => "预约代驾",
                "sub_button" => [
                    [
                        "type" => "miniprogram",
                        "name" => "立即下单",
                        "url" => "http://mp.weixin.qq.com",
                        "appid" => "wxff0de9d71076ff70",
                        "pagepath" => "pages/index/index"
                    ]
                ]
            ],
            [
                "type" => "click",
                "name" => "资费标准",
                "key" => "fee"
            ],
            [
                "type" => "click",
                "name" => "联系我们",
                "key" => "contact"
            ]
        ];
        $res = $this->app->menu->create($menus);
        LogService::save(json_encode($res));
        if (!$res) {
            throw new WeChatException(['msg' => '创建菜单失败']);
        }
    }

    private function prefixFee()
    {
        $fee_msg = "";
        $interval = TimeIntervalT::select();
        $start = StartPriceT::where('type', 1)->select();
        if (!empty($interval)) {
            foreach ($interval as $k => $v) {
                $fee_msg .= "  时间：(" . $v->time_begin . "-" . $v->time_end . ")" . "起步价" . $v->price . "元";
                $d = 0;
                foreach ($start as $k2 => $v2) {

                    if ($k2 == 0) {
                        $fee_msg .= "（" . $v2->distance . "公里内包含" . $v2->distance . "公里）;" . "\n";
                    } else if ($k2 == 1) {
                        $d += $v2->distance;
                        $fee_msg .= "超出起步里程后," . $v2->distance . "公里内包含" . $v2->distance . "公里,加收" . $v2->price . "元；";
                    } else {
                        $fee_msg .= "超出起步里程" . $d . "公里后," . $v2->distance . "公里内包含" . $v2->distance . "公里,加收" . $v2->price . "元；";
                        $d += $v2->distance;
                    }

                }
                $fee_msg .= "\n";
            }
        }
        $waitObj = WaitPriceT::find();
        $wait_msg = "  免费等候" . $waitObj->free . "分钟，等候超出" . $waitObj->free . "分钟后每1分钟加收" . $waitObj->price . "元。";

        return "资费标准：\n" . $fee_msg . $wait_msg;
    }

}