<?php


namespace app\api\service;


use app\api\model\TicketOpenT;
use app\api\model\TicketT;
use app\api\model\TicketUserT;
use app\api\model\TicketV;
use app\api\model\UserT;
use app\lib\enum\CommonEnum;
use app\lib\enum\TicketEnum;
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

    public static function userTicketSave($scene, $u_id, $phone)
    {
        $ticketOpen = TicketOpenT::where('scene', $scene)->find();
        if (!$ticketOpen || ($ticketOpen->open == CommonEnum::STATE_IS_FAIL) || self::checkTicketSend($phone, $scene)) {
            return [
                'ticket' => 2
            ];
        }
        $ticket = TicketT::where('scene', $scene)->find();
        if (!$ticket) {
            return [
                'ticket' => 2
            ];
        }
        $data = [
            'u_id' => $u_id,
            't_id' => $ticket->id,
            'phone' => '',
            'state' => CommonEnum::STATE_IS_OK,
            'money' => $ticket->price,
            //'time_begin' => $ticket->time_begin,
            //'time_end' => $ticket->time_end,
            'time_begin' => date('Y-m-d',time()),
            'time_end' => addDay(config('setting.ticket_time'), date('Y-m-d',time())),
            'scene' => $scene,
            'name' => $ticket->name,
            'phone' => $phone
        ];
        TicketUserT::create($data);
        //发送优惠券发放短信短信

        return [
            'ticket' => 1,
            'name' => $ticket->name,
            'time_begin' => $ticket->time_begin,
            'time_end' => $ticket->time_end,
            'money' => $ticket->price
        ];

    }

    private static function checkTicketSend($phone, $scene)
    {
        $ticket = UserT::where('phone', $phone)
            ->where('scene', $scene)
            ->count();
        return $ticket;

    }


    public function prefixTicketHandel($id, $state)
    {
        $ticket = TicketUserT::get($id);
        $ticket->state = $state;
        $res = $ticket->save();
        return $res;

    }


}