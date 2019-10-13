<?php


namespace app\api\service;


use app\api\model\CompanyT;
use app\api\model\TicketOpenT;
use app\api\model\WeatherT;
use app\lib\enum\CommonEnum;
use app\lib\exception\SaveException;
use app\lib\exception\UpdateException;
use think\Db;
use think\Exception;

class CompanyService
{
    public function save($params)
    {
        try {
            Db::startTrans();
            Token::getCurrentUid();
            $agent = CompanyT::create($params);
            if (!$agent) {
                throw new SaveException();
            }
            $this->companyInit($agent->id);
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    private function companyInit($company_id)
    {
        //新增恶劣天气补助信息
        WeatherT::create([
            'company_id' => $company_id,
            'state' => CommonEnum::STATE_IS_FAIL,
            'ratio' => 1
        ]);
        //初始化优惠券信息
        (new TicketOpenT())->saveAll([
            [
                'company_id' => $company_id,
                'open' => 2,
                'scene' => 1
            ],
            [
                'company_id' => $company_id,
                'open' => 2,
                'scene' => 2
            ]
        ]);

    }

    public function update($params)
    {
        $agent = CompanyT::update($params);
        if (!$agent) {
            throw  new UpdateException();
        }
    }

    public function agents($page, $size, $phone, $company, $username)
    {
        $agents = CompanyT::agents($page, $size, $phone, $company, $username);
        return $agents;
    }

}