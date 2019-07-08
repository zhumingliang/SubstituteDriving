<?php


namespace app\api\service;


use app\api\model\OrderListT;
use app\api\model\OrderT;
use app\lib\enum\CommonEnum;
use app\lib\enum\OrderEnum;
use app\lib\exception\SaveException;

class OrderService
{
    public function saveMiniOrder($params)
    {
        $params['u_id'] = Token::getCurrentUid();
        $params['phone'] = Token::getCurrentTokenVar('phone');
        $params['from'] = OrderEnum::FROM_MINI;
        $params['type'] = OrderEnum::NOT_FIXED_MONEY;
        $params['state'] = OrderEnum::ORDER_NO;
        $params['transfer'] = CommonEnum::STATE_IS_FAIL;
        $o_id = $this->saveOrder($params);
        $this->saveOrderList($o_id);
        return $o_id;
    }

    public function orderListHandel()
    {
        //查询待处理订单并将订单状态改为处理中
        $orderList = OrderListT::where('state', OrderEnum::ORDER_LIST_NO)
            ->find();
        if (!$orderList) {
            return false;
        }
        $orderList->state = OrderEnum::ORDER_LIST_ING;
        $orderList->save();

        //获取订单信息并检测订单状态
        $order = OrderT::where('id', $orderList->o_id);
        if (!$order || $order->state != OrderEnum::ORDER_NO) {
            return false;
        }

        //查找司机并推送


    }

    private function saveOrder($data)
    {
        $order = OrderT::create($data);
        if (!$order) {
            throw  new SaveException(['msg' => '下单失败']);
        }
        return $order->id;
    }

    private function saveOrderList($o_id)
    {
        $data = [
            'o_id' => $o_id,
            'state' => OrderEnum::ORDER_LIST_NO
        ];
        OrderListT::create($data);

    }


    private function findDriverToPush($order)
    {
        
    }

}