<?php


namespace app\api\service;


use app\api\model\StartPriceT;
use app\api\model\TimeIntervalT;
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

}