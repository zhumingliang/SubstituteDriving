<?php


namespace app\api\service;


use app\api\model\DriverT;
use app\api\model\RechargeT;
use app\api\model\WalletRecordV;
use app\api\validate\Driver;
use app\lib\enum\CommonEnum;
use app\lib\exception\AuthException;
use app\lib\exception\SaveException;

class WalletService
{

    public function recharge($params)
    {
        $grade = Token::getCurrentTokenVar('type');
        $params['admin_id'] = Token::getCurrentUid();
        $params['state'] = CommonEnum::STATE_IS_OK;
        if ($grade !== 'manager') {
            throw new AuthException();
        }
        $this->checkDriver($params['d_id']);
        $recharge = RechargeT::create($params);
        if (!$recharge) {
            throw new SaveException();
        }
    }

    public function recharges($page, $size)
    {
        $list = RechargeT::rechargesForManager($page, $size);
        return $list;

    }

    public function driverRecharges($page, $size, $d_id)
    {
        $list = RechargeT::rechargesForDriver($page, $size, $d_id);
        return $list;

    }


    private function checkDriver($d_id)
    {
        $driver = DriverT::where('id', $d_id)->find();
        if (!$driver) {
            throw new SaveException(['msg' => '司机不存在']);
        }
        if ($driver->state == CommonEnum::STATE_IS_FAIL) {
            throw new SaveException(['msg' => '司机状态异常']);
        }
    }

    public function driverRecords($page, $size)
    {
        $grade = Token::getCurrentTokenVar('type');
        if ($grade == "driver") {
            $d_id = Token::getCurrentUid();
            $records = WalletRecordV::recordsToDriver($page, $size, $d_id);

            return [
                'records' => $records,
                'balance' => $this->driverBalance($d_id)
            ];
        } else {
            throw  new AuthException();
        }
    }

    private function driverBalance($d_id)
    {
        $balance = WalletRecordV::driverBalance($d_id);
        return $balance;

    }

    public function managerRecords($page, $size, $driver, $time_begin, $time_end)
    {
        $grade = Token::getCurrentTokenVar('type');
        if ($grade == "manager") {
            $records = WalletRecordV::managerRecords($page, $size, $driver, $time_begin, $time_end);

        } else {
            throw  new AuthException();
        }
        return $records;

    }
}