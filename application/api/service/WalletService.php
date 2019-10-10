<?php


namespace app\api\service;


use app\api\model\DriverIncomeV;
use app\api\model\DriverT;
use app\api\model\RechargeT;
use app\api\model\WalletRecordV;
use app\api\validate\Driver;
use app\lib\enum\CommonEnum;
use app\lib\enum\DriverEnum;
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
        //发送短信通知

        $driver = DriverT::where('id', $params['d_id'])->find();
        $phone = $driver->phone;
        $data = [
            'username' => $driver->username,
            'money' => $params['money'],
            'balance' => $this->driverBalance($params['d_id'])
        ];
        (new SendSMSService())->sendRechargeSMS($phone, $data);
    }

    public function recharges($d_id, $page, $size)
    {
        $list = RechargeT::rechargesForManager($d_id, $page, $size);
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

    public function driverRecords($page, $size, $time_begin, $time_end)
    {
        $grade = Token::getCurrentTokenVar('type');
        if ($grade == "driver") {
            $d_id = Token::getCurrentUid();
            $records = WalletRecordV::recordsToDriver($page, $size, $d_id, $time_begin, $time_end);

            return [
                'records' => $records,
                'balance' => $this->driverBalance($d_id),
                'income' => DriverIncomeV::TimeIncome($d_id, $time_begin, $time_end),
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

    public function managerRecords($page, $size, $driver_name, $time_begin, $time_end)
    {
        $grade = Token::getCurrentTokenVar('type');
        $company_id = Token::getCurrentTokenVar('company_id');
        if ($grade != "manager") {
            throw  new AuthException();

        }
        $balance = 0;
        $income = 0;
        $driver_id = 0;
        if (!empty($driver_name)) {
            $driver = DriverT::where('company_id', $company_id)
                ->where('username', $driver_name)->find();
            if ($driver) {
                $driver_id = $driver->id;
                $balance = $this->driverBalance($driver_id);
                $income = DriverIncomeV::TimeIncome($driver_id, $time_begin, $time_end);
            }

        }

        $records = WalletRecordV::managerRecords($company_id,$page, $size, $driver_id, $time_begin, $time_end);
        return [
            'records' => $records,
            'balance' => $balance,
            'income' => $income,
        ];
    }

    public function checkDriverBalance($d_id)
    {

        $balance = $this->driverBalance($d_id);
        if ($balance < config('setting.balance_limit')) {
            DriverT::update(['online' => DriverEnum::OFFLINE], ['id' => $d_id]);
            $push_data = [
                'type' => 'online',
                "order_info" => ['msg' => '余额不足，请充值']
            ];
            GatewayService::sendToDriverClient($d_id, $push_data);
            return true;
        }
        return false;
    }
}