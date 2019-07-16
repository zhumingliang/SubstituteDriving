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
        $info_arr = json_decode($info, true);
        if (!count($info_arr)) {
            throw new UpdateException(['msg' => '更新数据不能为空']);
        }
        $res = (new StartPriceT())->saveAll($info_arr);
        if (!$res) {
            throw new UpdateException();

        }

    }

    public function intervalUpdate($info)
    {
        $info_arr = json_decode($info, true);
        if (!count($info_arr)) {
            throw new UpdateException(['msg' => '更新数据不能为空']);
        }
        $res = (new TimeIntervalT())->saveAll($info_arr);
        if (!$res) {
            throw new UpdateException();

        }

    }

    public function priceInfoForMINI($params)
    {
        $lat = $params['lat'];
        $lng = $params['lng'];
        $tickets = (new TicketService())->userTickets();;
        //附近2km
        $drivers = 0;
        $start = $this->startPrice();
        $wait = $this->wait();
        $interval = $this->intervalTime();


        return [
            'tickets' => $tickets,
            'drivers' => $drivers,
            'start' => $start,
            'wait' => $wait,
            'interval' => $interval,

        ];

    }

    private function startPrice()
    {
        $info = StartPriceT::where('type', 1)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->hidden(['type', 'state', 'area', 'create_time', 'update_time'])
            ->order('order')
            ->select();
        return $info;
    }

    private function intervalTime()
    {
        $info = TimeIntervalT::where('state', CommonEnum::STATE_IS_OK)
            ->hidden(['state', 'create_time', 'update_time', 'area'])
            ->order('create_time')
            ->select();
        return $info;

    }

    private function wait()
    {
        $info = WaitPriceT::field('id,free,price')
            ->find();
        return $info;
    }

    public function priceInfoForDriver()
    {

        $start = $this->startPrice();
        if (count($start)) {
            foreach ($start as $k => $v) {
                if ($k == 0) {
                    $start[$k]['price'] = (new OrderService())->getStartPrice($v['price']);
                }
            }
        }

        $wait = $this->wait();

        return [
            'start' => $start,
            'wait' => $wait];

    }

}