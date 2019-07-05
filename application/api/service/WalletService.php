<?php


namespace app\api\service;


use app\api\model\DriverT;
use app\api\model\RechargeT;
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
}