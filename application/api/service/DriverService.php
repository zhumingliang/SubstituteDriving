<?php


namespace app\api\service;


use app\api\model\DriverT;
use app\api\model\WalletRecordV;
use app\lib\enum\CommonEnum;
use app\lib\exception\SaveException;

class DriverService
{
    public function save($params)
    {
        $params['pwd'] = sha1($params['pwd']);
        $params['admin_id'] = Token::getCurrentUid();
        $params['state'] = CommonEnum::STATE_IS_OK;
        $driver = DriverT::create($params);
        if (!$driver) {
            throw new SaveException();
        }

    }

    public function drivers($page, $size, $time_begin, $time_end, $username, $account,$online)
    {

        $drivers = WalletRecordV::drivers($page, $size, $time_begin, $time_end, $username, $account,$online);
        return $drivers;

    }

}