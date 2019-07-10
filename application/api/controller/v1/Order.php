<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\OrderService;
use app\api\service\SendSMSService;
use app\lib\exception\SuccessMessage;
use app\lib\exception\SuccessMessageWithData;

class Order extends BaseController
{
    /**
     * @api {POST} /api/v1/order/mini/save  小程序端-代驾下单
     * @apiGroup   MINI
     * @apiVersion 1.0.1
     * @apiDescription   小程序端-代驾下单
     * @apiExample {post}  请求样例:
     *    {
     *       "start":"铜陵市维也纳大酒店",
     *       "start_lat":"12.1212",
     *       "start_lng":"23.121",
     *       "end":铜陵市高速",
     *       "end_lat":"12.1212",
     *       "end_lng":"21.1212",
     *       "t_id":1
     *     }
     * @apiParam (请求参数说明) {String} start  出发地
     * @apiParam (请求参数说明) {String} start_lat  出发地纬度
     * @apiParam (请求参数说明) {String} start_lng  出发地经度
     * @apiParam (请求参数说明) {String} end  目的地
     * @apiParam (请求参数说明) {String} end_lat  目的地纬度
     * @apiParam (请求参数说明) {String} end_lng  目的地经度
     * @apiParam (请求参数说明) {int} t_id  优惠id
     * @apiSuccessExample {json} 返回样例:
     *{"id":1,"errorCode":0,"data":{"id":1}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {int} id 订单id
     */
    public function saveMiniOrder()
    {
        $params = $this->request->param();
        $id = (new OrderService())->saveMiniOrder($params);
        return json(new SuccessMessageWithData(['id' => $id]));
    }

    /**
     * 处理等待推送订单队列
     */
    public function orderListHandel()
    {
        (new OrderService())->orderListHandel();

    }

    /**
     * 处理推送信息
     */
    public function handelDriverNoAnswer()
    {
        (new OrderService())->handelDriverNoAnswer();
    }


    /**
     * @api {POST} /api/v1/order/push/handel  Android司机端-接单/拒单
     * @apiGroup   Android
     * @apiVersion 1.0.1
     * @apiDescription   Android司机端-接单/拒单
     * @apiExample {post}  请求样例:
     *    {
     *       "p_id": 1,
     *       "type":2"
     *     }
     * @apiParam (请求参数说明) {int} p_id  推送id
     * @apiParam (请求参数说明) {int} type  推送处理状态：2 | 接单；3 | 拒单
     * @apiSuccessExample {json} 返回样例:
     *{"msg":"ok","errorCode":0}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     */
    public function orderPushHandel()
    {
        $params = $this->request->param();
        (new OrderService())->orderPushHandel($params);
        return json(new SuccessMessage());

    }


    public function locationAdd($lat, $lng, $d_id)
    {

        (new SendSMSService())->sendOrderSMS('18956225230', ['code' => '*****' . substr('sajdlkjdsk21312', 5), 'order_time' => date('H:i', time())]);

        /*  $redis = new \Redis();
          $redis->connect('127.0.0.1', 6379, 60);

          $ret = $redis->rawCommand('geoadd', 'drivers_tongling', $lat, $lng, $d_id);
          var_dump($ret);*/

    }

    public function location($d_id)
    {
        $redis = new \Redis();
        $redis->connect('127.0.0.1', 6379, 60);
        var_dump($redis->rawCommand('geopos', 'drivers_tongling', $d_id));
    }

    public function deleteLocation($d_id)
    {
        $redis = new \Redis();
        $redis->connect('127.0.0.1', 6379, 60);
        var_dump($redis->rawCommand('zrem', 'drivers_tongling', $d_id));
    }

    public function radius($lat, $lng, $type)
    {
        $redis = new \Redis();
        $redis->connect('127.0.0.1', 6379, 60);
        //查询所有司机并按距离排序
        $list = $redis->rawCommand('georadius', 'drivers_tongling', $lng, $lat, '1000000', 'km', $type);
        print_r($list);
    }

}