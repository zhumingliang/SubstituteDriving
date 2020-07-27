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
        $phone_arr = explode(',', $u_id);
        $ticket = TicketT::where('id', $t_id)->find();
        if (!$ticket) {
            throw new SaveException(['msg' => '卡券不存在']);
        }
        $data = array();
        foreach ($phone_arr as $k => $v) {

            $data[] = [
                'phone' => $v,
                't_id' => $t_id,
                'name' => $ticket->name,
                'money' => $ticket->price,
                'time_begin' => date('Y-m-d', time()),
                'time_end' => addDay($ticket->user_limit, date('Y-m-d', time())),
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
        //派送优惠券通知
        foreach ($data as $k => $v) {
            (new SendSMSService())->sendTicketSMS($v['phone'], ['phone' => '19855751988']);
        }

        return true;

    }

    public function ticketsForCMS($page, $size, $time_begin, $time_end, $key)
    {
        $company_id = Token::getCurrentTokenVar('company_id');
        $ticks = TicketV::ticketsForCMS($company_id, $page, $size, $time_begin, $time_end, $key);
        return $ticks;
    }

    public function userTickets()
    {
        /* $u_id = Token::getCurrentUid();
         $scene = Token::getCurrentTokenVar('scene');
         $phone = Token::getCurrentTokenVar('phone');
         if (empty($phone)) {
             $ticks = TicketUserT::userTickets($u_id, $scene);

         } else {
             $ticks = TicketUserT::userPhoneTickets($phone);
         }*/
        $phone =Token::getCurrentTokenVar('phone');
        $ticks = TicketUserT::userPhoneTickets($phone);
        return $ticks;
    }

    public static function userTicketSave($scene, $u_id, $phone, $company_id = 1)
    {
        $ticketOpen = TicketOpenT::where('company_id', $company_id)
            ->where('scene', $scene)
            ->find();
        if (!$ticketOpen || ($ticketOpen->open == CommonEnum::STATE_IS_FAIL) || self::checkTicketSend($phone, $scene)) {
            return [
                'ticket' => 2
            ];
        }
        $ticket = TicketT::where('company_id', $company_id)
            ->where('scene', $scene)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->whereTime('time_begin', '<=', date('Y-m-d H:i:s'))
            ->whereTime('time_end', '>=', date('Y-m-d H:i:s'))
            ->order('create_time desc')
            ->find();
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
            'time_begin' => date('Y-m-d', time()),
            'time_end' => addDay($ticket->user_limit, date('Y-m-d', time())),
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
        $ticket = TicketUserT::where('phone', $phone)
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

    public function phoneTickets($phone)
    {
        $ticks = TicketUserT::userPhoneTickets($phone);
        return $ticks;
    }

}