<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\OrderService;
use app\lib\exception\SuccessMessageWithData;

class Order extends BaseController
{
    public function saveMiniOrder()
    {
        $params = $this->request->param();
        $id = (new OrderService())->saveMiniOrder($params);
        return json(new SuccessMessageWithData(['id' => $id]));
    }

    public function orderListHandel()
    {
        (new OrderService())->orderListHandel();

    }

    public function locationAdd($lat, $lng, $d_id)
    {
        $redis = new \Redis();
        $redis->connect('127.0.0.1', 6379, 60);

        $ret = $redis->rawCommand('geoadd', 'drivers_location', $lat, $lng, $d_id);
        var_dump($ret);

    }

    public function location($d_id)
    {
        $redis = new \Redis();
        $redis->connect('127.0.0.1', 6379, 60);
        var_dump($redis->rawCommand('geopos', 'drivers_location', $d_id));
    }

}