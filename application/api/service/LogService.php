<?php


namespace app\api\service;


use app\api\model\LogT;

class LogService
{
    public static function save($msg)
    {
        LogT::create([
            'msg' => $msg
        ]);

    }

}