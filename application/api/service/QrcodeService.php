<?php


namespace app\api\service;


use Endroid\QrCode\QrCode;

class QrcodeService
{
    /*
       * 生成二维码图片
       */
    public
    function qr_code($link)
    {
        $sha1 = sha1($link);
        $qrcode_dir = dirname($_SERVER['SCRIPT_FILENAME']) . '/static/qrcode/';
        if (!file_exists($qrcode_dir)) mkdir($qrcode_dir, 0777, true);
        $file_name = $qrcode_dir . $sha1 . '.png';
        $qrCode = new QrCode($link);
        $qrCode->writeFile($file_name);
        return '/static/qrcode/' . $sha1 . '.png';
    }

}