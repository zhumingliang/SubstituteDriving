<?php


namespace app\api\model;


use app\api\service\LogService;
use app\lib\enum\CommonEnum;
use think\Model;

class TicketUserT extends Model
{
    public static function userTickets($u_id, $scene)
    {
        $now = date('Y-m-d H:i');
        $tickets = self::where('u_id', $u_id)
            ->where('scene', $scene)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->whereTime('time_begin', '<=', $now)
            ->whereTime('time_end', '>=', $now)
            ->field('id,name,money,time_begin,time_end')
            ->select();
        return $tickets;

    }

    public static function userPhoneTickets($phone)
    {
        $now = date('Y-m-d H:i');
        $tickets = self::where('phone', $phone)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->whereTime('time_begin', '<=', $now)
            ->whereTime('time_end', '>=', $now)
            ->field('id,name,money,time_begin,time_end')
            ->fetchSql(true)
            ->select();
        LogService::save($tickets);
        return $tickets;

    }

}