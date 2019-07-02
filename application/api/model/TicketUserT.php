<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class TicketUserT extends Model
{
    public static function userTickets($u_id)
    {
        $now = date('Y-m-d H:i');
        $tickets = self::where('u_id', $u_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->whereTime('time_begin', '<=', $now)
            ->whereTime('time_end', '>=', $now)
            ->field('t_id as id,name,money,time_begin,time_end')
            ->select();
        return $tickets;

    }

}