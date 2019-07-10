<?php

namespace app\api\controller\v1;

use app\lib\exception\SuccessMessage;
use app\lib\exception\SuccessMessageWithData;
use GatewayClient\Gateway;

class Index
{
    public function index()
    {
        $distance = 1;
        $farRule = [
            ['distance' => 5, 'price' => 0],
            ['distance' => 1, 'price' => 5],
            ['distance' => 2, 'price' => 2],
        ];
        $money_new = 0;
        $count = count($farRule) - 1;
        foreach ($farRule as $k => $v) {
            if ($distance <= 0) {
                return $money_new;
                break;
            }
            if ($count > $k) {
                $money_new += $v['price'];
                $distance -= $v['distance'];
            } else {
                $money_new += $v['price'] * ceil($distance / $v['distance']);
            }

        }
        echo $money_new;
    }

    public function send($client_id)
    {
        //  var_dump(Gateway::sendToClient($client_id, 1));
    }

}
