<?php


namespace app\api\service;


use app\api\model\TicketT;
use app\api\model\TicketUserT;
use app\api\model\TicketV;
use app\lib\enum\CommonEnum;
use app\lib\exception\SaveException;

class TicketService
{
    public function sendTicket($u_id, $t_id)
    {
        $u_id_arr = explode(',', $u_id);
        $ticket = TicketT::where('id', $t_id)->find();
        if (!$ticket) {
            throw new SaveException(['msg' => '卡券不存在']);
        }
        $data = array();
        foreach ($u_id_arr as $k => $v) {
            $data[] = [
                'u_id' => $v,
                't_id' => $t_id,
                'name' => $ticket->name,
                'money' => $ticket->price,
                'time_begin' => $ticket->time_begin,
                'time_end' => $ticket->time_end,
                'scene' => $ticket->scene,
                'state' => CommonEnum::STATE_IS_OK
            ];
        }

        if (!count($data)) {
            return false;
        }
        $res = (new TicketUserT())->saveAll($data);
        if (!$res) {
            throw new SaveException();
        }
        return true;

    }

    public function ticketsForCMS($page, $size, $time_begin, $time_end, $key)
    {
        $ticks = TicketV::ticketsForCMS($page, $size, $time_begin, $time_end, $key);
        return $ticks;
    }

    public function userTickets()
    {
        $u_id = Token::getCurrentUid();
        $ticks = TicketUserT::userTickets($u_id);
        return $ticks;
    }


}