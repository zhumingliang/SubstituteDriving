<?php
/**
 * Created by PhpStorm.
 * User: mingliang
 * Date: 2018/11/28
 * Time: 11:56 PM
 */


namespace zml\tp_aliyun;

use app\api\model\LogT;
use think\Exception;
use think\Validate;

class SendSms
{
    static protected $instance;
    protected $accessKeyId;
    protected $accessSecret;
    protected $TemplateLoginCode;
    protected $TemplateRegisterCode;
    protected $TemplateDriverCode;
    protected $TemplateRechargeCode;
    protected $signName;
    protected $requestHost = "http://dysmsapi.aliyuncs.com";
    protected $requestUrl;
    protected $signature;
    protected $requestParas;
    protected $error;

    public function __construct($options = [])
    {
        ini_set('date.timezone', 'Asia/Shanghai');
        date_default_timezone_set("GMT");
        isset($options["AccessKeyId"]) && $this->accessKeyId = $options["AccessKeyId"];
        isset($options["AccessSecret"]) && $this->accessSecret = $options["AccessSecret"];
        isset($options["TemplateRegisterCode"]) && $this->TemplateRegisterCode = $options["TemplateRegisterCode"];
        isset($options["TemplateLoginCode"]) && $this->TemplateLoginCode = $options["TemplateLoginCode"];
        isset($options["TemplateDriverCode"]) && $this->TemplateDriverCode = $options["TemplateDriverCode"];
        isset($options["TemplateRechargeCode"]) && $this->TemplateRechargeCode = $options["TemplateRechargeCode"];
        isset($options["SignName"]) && $this->signName = $options["SignName"];
    }

    /**
     * @param array $options
     * @return SendSms
     * @throws Exception
     */
    static public function instance($options = [])
    {
        $options = self::getOptions($options);
        $sn = self::getSn($options);
        if (isset(self::$instance[$sn])) {
            return self::$instance[$sn];
        } else {
            return self::$instance[$sn] = new static($options);
        }
    }

    /**
     * @title setAccessKeyId
     * @description 设置AccessKey
     * @param string $accessKeyId
     * @return $this
     * @author Mikkle
     */
    public function setAccessKeyId($accessKeyId = "")
    {
        if (!empty($accessKeyId) && is_string($accessKeyId)) {
            $this->accessKeyId = $accessKeyId;
        }
        return $this;
    }

    /**
     * @title setAppSecret
     * @description
     * @param string $accessSecret
     * @return $this
     * @author Mikkle
     */
    public function setAppSecret($accessSecret = "")
    {
        if (!empty($accessSecret) && is_string($accessSecret)) {
            $this->accessSecret = $accessSecret;
        }
        return $this;
    }

    public function setTemplateCode($templateCode = "")
    {
        if (!empty($templateCode) && is_string($templateCode)) {
            $this->templateCode = $templateCode;
        }
        return $this;
    }

    public function setSignName($signName = "")
    {
        if (!empty($signName) && is_string($signName)) {
            $this->signName = $signName;
        }
        return $this;
    }


    /**
     * 发送
     */
    public function send($phone, $params, $type)
    {
        try {
            /* if (!$this->checkParams($phone, $code)) {

                 throw  new  Exception($this->error);
             }*/
            if ($this->createRequestUrl($phone, $params, $type) && $this->signature) {
                $url = "{$this->requestHost}/?Signature={$this->signature}{$this->requestUrl}";
                $res = $this->fetchContent($url);
                return json_decode($res, true);
            } else {
                LogT::create(['msg' => '参数错误']);
            }
        } catch (Exception $e) {
            LogT::create(['msg' => $e->getMessage()]);
        }
    }


    protected function createRequestUrl($phone, $params, $type)
    {
        try {

            if ($type == 'register') {
                $templateCode = $this->TemplateRegisterCode;
            } else if ($type == 'login') {
                $templateCode = $this->TemplateLoginCode;
            } else if ($type == 'recharge') {
                $templateCode = $this->TemplateRechargeCode;
            } else {
                $templateCode = $this->TemplateDriverCode;
            }

            $requestParams = [
                //'RegionId' => 'cn-hangzhou', // API支持的RegionID，如短信API的值为：cn-hangzhou
                'AccessKeyId' => $this->accessKeyId, // 访问密钥，在阿里云的密钥管理页面创建
                "Format" => 'JSON', // 返回值类型，没传默认为JSON，可选填值：XML
                "SignatureMethod" => 'HMAC-SHA1', // 编码(固定值不用改)
                "SignatureVersion" => '1.0', // 版本(固定值不用改)
                'SignatureNonce' => uniqid(mt_rand(0, 0xffff), true), // 用于请求的防重放攻击的唯一加密盐
                'Timestamp' => date('Y-m-d\TH:i:s\Z'), // 格式为：yyyy-MM-dd’T’HH:mm:ss’Z’；时区为：GMT
                'Action' => 'SendSms', // API的命名，固定值，如发送短信API的值为：SendSms
                'Version' => '2017-05-25', // API的版本，固定值，如短信API的值为：2017-05-25
                'PhoneNumbers' => $phone, // 短信接收号码
                'SignName' => $this->signName, // 短信签名
                'TemplateCode' => $templateCode, // 短信模板ID
                'TemplateParam' => json_encode($params, JSON_UNESCAPED_UNICODE),
            ];

            ksort($requestParams);
            $requestUrl = "";
            foreach ($requestParams as $key => $value) {
                $requestUrl .= "&" . $this->encode($key) . "=" . $this->encode($value);
            }
            $this->requestUrl = $requestUrl;

            $stringToSign = "GET&%2F&" . $this->encode(substr($requestUrl, 1));
            // 清除最后一个&
            $this->signature = base64_encode(hash_hmac('sha1', $stringToSign, $this->accessSecret . '&', true));
            $this->requestParas["Signature"] = $this->signature;
            if (empty($this->signature)) {
                throw  new  Exception("URL加密错误");
            }
            return true;
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    protected function checkParams($phone = "", $code = "")
    {
        if (empty($this->accessKeyId) || empty($this->accessSecret) || empty($this->templateCode)) {
            $this->error = "获取短息发送接口参数缺失";
            return false;
        }
        $validate = new Validate([
            ['phone', 'require|regex:/1[34578]{1}\d{9}$/', '手机号不能为空|手机号错误'],
            ['code', 'require', '验证码不存在'],
        ]);
        $data = [
            "phone" => $phone,
            "code" => $code,
        ];
        if (!$validate->check($data)) {
            $this->error = $validate->getError();
            return false;
        }
        return true;
    }

    protected function encode($url)
    {
        $url = urlencode($url);
        $url = preg_replace('/\+/', '%20', $url);
        $url = preg_replace('/\*/', '%2A', $url);
        $url = preg_replace('/%7E/', '~', $url);
        return $url;
    }


    /**
     * @param array $options
     * @return array|mixed
     * @throws Exception
     */
    static protected function getOptions($options = [])
    {

        if (empty($options) && !empty(Config("alisms.default_options_name"))) {
            $name = "alisms" . "." . Config("alisms.default_options_name");
            $options = Config("$name");

        } elseif (is_string($options) && !empty(Config("alisms.$options"))) {
            $options = Config("alisms.$options");
        }
        if (empty($options)) {
            $error[] = "获取短息发送接口参数缺失";
            throw new Exception("获取短息发送接口参数缺失");
        } elseif (isset($options["AccessKeyId"]) && isset($options["AccessSecret"])) {
            return $options;
        } else {
            throw new Exception("短息发送接口参数不完整");
        }
    }

    /**
     * @param array $options
     * @return string
     * @throws Exception
     */
    static protected function getSn($options = [])
    {
        $options = self::getOptions($options);
        return md5("{$options["AccessKeyId"]}{$options["AccessSecret"]}");
    }


    /**
     * @param $url
     * @return bool|mixed|string
     */
    private function fetchContent($url)
    {
        if (function_exists("curl_init")) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "x-sdk-client" => "php/2.0.0"
            ));
            $rtn = curl_exec($ch);
            if ($rtn === false) {
                trigger_error("[CURL_" . curl_errno($ch) . "]: " . curl_error($ch), E_USER_ERROR);
            }
            curl_close($ch);
            return $rtn;
        }

        $context = stream_context_create(array(
            "http" => array(
                "method" => "GET",
                "header" => array("x-sdk-client: php/2.0.0"),
            )
        ));
        return file_get_contents($url, false, $context);
    }

}