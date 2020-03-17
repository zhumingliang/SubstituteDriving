<?php
/**
 * Created by singwa
 * User: singwa
 * motto: 现在的努力是为了小时候吹过的牛逼！
 * Time: 01:17
 */
declare(strict_types=1);
namespace app\lib\sms;

interface SmsBase {
    public static function sendCode(string $phone, int $code);
    public static function sendTemplate(string $phone, array $params,string $templateType);
}