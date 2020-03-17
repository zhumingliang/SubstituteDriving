<?php
/**
 * Created by singwa
 * User: singwa
 * motto: 现在的努力是为了小时候吹过的牛逼！
 * Time: 01:14
 */
declare(strict_types=1);
namespace app\lib\sms;
class JdSms implements SmsBase {
    public static function sendCode(string $phone, int $code) {
        return true;
    }
}