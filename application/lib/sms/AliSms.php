<?php
/**
 * Created by singwa
 * User: singwa
 * motto: 现在的努力是为了小时候吹过的牛逼！
 * Time: 23:19
 */
declare(strict_types=1);

namespace app\lib\sms;

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;
use think\facade\Log;

class AliSms implements SmsBase
{
    /**
     * 阿里云发送短信验证码的场景
     */
    public static function sendCode(string $phone, int $code): bool
    {
        if (empty($phone) || empty($code)) {
            return false;
        }

        AlibabaCloud::accessKeyClient(config("aliyun.access_key_id"),
            config("aliyun.access_key_secret"))
            ->regionId(config("aliyun.region_id"))
            ->asDefaultClient();

        $templateParam = [
            "code" => $code,
        ];
        try {
            $result = AlibabaCloud::rpc()
                ->product('Dysmsapi')
                // ->scheme('https') // https | http
                ->version('2017-05-25')
                ->action('SendSms')
                ->method('POST')
                ->host(config("aliyun.host"))
                ->options([
                    'query' => [
                        'RegionId' => config("aliyun.region_id"),
                        'PhoneNumbers' => $phone,
                        'SignName' => config("aliyun.sign_name"),
                        'TemplateCode' => config("aliyun.template_code"),
                        'TemplateParam' => json_encode($templateParam),
                    ],
                ])
                ->request();
        } catch (ClientException $e) {
            Log::error("alisms-sendCode-{$phone}ClientException" . $e->getErrorMessage());
            return false;
            //echo $e->getErrorMessage() . PHP_EOL;
        }
        if (isset($result['Code']) && $result['Code'] == "OK") {
            return true;
        }
        return false;

    }

    public static function sendTemplate(string $phone, array $params, string $templateType)
    {
        $templateCode = self::getTemplateCode($templateType, $params);

        if (empty($phone)) {
            return false;
        }

        AlibabaCloud::accessKeyClient(config("alisms.ok.AccessKeyId"),
            config("aliyun.AccessSecret"))
            ->regionId(config("aliyun.RegionId"))
            ->asDefaultClient();

        $templateParam = $params;
        try {
            $result = AlibabaCloud::rpc()
                ->product('Dysmsapi')
                // ->scheme('https') // https | http
                ->version('2017-05-25')
                ->action('SendSms')
                ->method('POST')
                ->host(config("alisms.ok.Host"))
                ->options([
                    'query' => [
                        'RegionId' => config("alisms.ok.RegionId"),
                        'PhoneNumbers' => $phone,
                        'SignName' => config("alisms.ok.SignName"),
                        'TemplateCode' => $templateCode,
                        'TemplateParam' => json_encode($templateParam),
                    ],
                ])
                ->request();
        } catch (ClientException $e) {
            Log::error("alisms-sendCode-{$phone}ClientException" . $e->getErrorMessage());
            return false;
            //echo $e->getErrorMessage() . PHP_EOL;
        }
        if (isset($result['Code']) && $result['Code'] == "OK") {
            return true;
        }
        return false;
    }

    private static function getTemplateCode($templateType, $params)
    {
        $name = "alisms" . "." . Config("alisms.default_options_name");
        $options = Config("$name");
        if ($templateType == 'register') {
            $templateCode = $options['TemplateRegisterCode'];
        } else if ($templateType == 'login') {
            $templateCode = $options['TemplateLoginCode'];
        } else if ($templateType == 'recharge') {
            $templateCode = $options['TemplateRechargeCode'];
        } else if ($templateType == 'mini') {
            $templateCode = $options['TemplateMINICode'];
        } else if ($templateType == 'driveCreateOrder') {
            $templateCode = $options['TemplateDriverCreateOrderCode'];
        } else if ($templateType == 'orderComplete') {
            if (empty($params['phone'])) {
                $templateCode = $options['TemplateOrderCompleteCode'];

            } else {
                $templateCode = $options['TemplateOrderCompleteCode2'];

            }
        } elseif ($templateType == 'ticket') {
            $templateCode = $options['TemplateTicketCode'];
        } else {
            $templateCode = $options['TemplateDriverCode'];
        }

        return $templateCode;
    }
}