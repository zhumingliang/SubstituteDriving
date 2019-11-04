<?php


namespace app\api\service;


use app\api\model\StartPriceT;
use app\api\model\TimeIntervalT;
use app\api\model\WaitPriceT;
use app\lib\enum\CommonEnum;
use app\lib\exception\UpdateException;

class SystemPriceService
{

    public function startUpdate($info)
    {
        /* $info_arr = json_decode($info, true);
         if (!count($info_arr)) {
             throw new UpdateException(['msg' => '更新数据不能为空']);
         }
         $res = (new StartPriceT())->saveAll($info_arr);
         if (!$res) {
             throw new UpdateException();

         }*/

        $res = StartPriceT::update($info);
        if (!$res) {
            throw new UpdateException();

        }

    }

    public function intervalUpdate($info)
    {
        if (!empty($info['id'])) {
            $res = TimeIntervalT::update($info);
        } else {
            $info['company_id'] = Token::getCurrentTokenVar('company_id');
            $res = TimeIntervalT::create($info);
        }
        if (!$res) {
            throw new UpdateException();

        }

    }

    public function initMINIPrice($params)
    {
        $company_id = Token::getCurrentTokenVar('company_id');
        $start = $this->startPrice($company_id);
        $wait = $this->wait($company_id);
        $interval = $this->intervalTime($company_id);


        return [
            'start' => $start,
            'wait' => $wait,
            'interval' => $interval,

        ];

    }

    private function startPrice($company_id)
    {
        $info = StartPriceT::where('company_id', $company_id)
            ->where('type', 1)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->hidden(['type', 'state', 'area', 'create_time', 'update_time'])
            ->order('order')
            ->select();
        return $info;
    }

    private function intervalTime($company_id)
    {
        $info = TimeIntervalT::where('company_id', $company_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->hidden(['state', 'create_time', 'update_time', 'area'])
            ->order('create_time')
            ->select();
        return $info;

    }

    private function wait($company_id)
    {
        $info = WaitPriceT::where('company_id', $company_id)->field('id,free,price')
            ->find();
        return $info;
    }

    public function priceInfoForDriver($id)
    {

        $company_id = Token::getCurrentTokenVar('company_id');
        $start = $this->startPrice($company_id);
        if (count($start)) {
            foreach ($start as $k => $v) {
                if ($k == 0) {
                    $start[$k]['price'] = (new OrderService())->getStartPrice($company_id, $v['price'], $id);
                }
            }
        }

        $wait = $this->wait($company_id);

        LogService::save('d_id:' . Token::getCurrentUid());
        LogService::save('id:' . $id);
        LogService::save('start:' . json_encode($start));
        return [
            'start' => $start,
            'wait' => $wait
        ];

    }

    public function loginInit($params)
    {
        $lat = $params['lat'];
        $lng = $params['lng'];
        $company_id = Token::getCurrentTokenVar('company_id');
        $tickets = (new TicketService())->userTickets();
        //附近2km
        $drivers = (new DriverService())->getDriversCountWithLocation($company_id, $lat, $lng);
        return [
            'tickets' => $tickets,
            'drivers' => $drivers,
            'interval' => TimeIntervalT::where('company_id', $company_id)
                ->where('state', CommonEnum::STATE_IS_OK)
                ->field('time_begin,time_end,price')
                ->select()->toArray()
        ];
    }


}