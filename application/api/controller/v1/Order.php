<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\OrderService;
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
     * @api {POST} /api/v1/order/mini/cancel 小程序端-取消订单
     * @apiGroup   MINI
     * @apiVersion 1.0.1
     * @apiDescription   小程序端-取消订单
     * @apiExample {post}  请求样例:
     *    {
     *       "id": 1,
     *       "remark":"无人接单"
     *     }
     * @apiParam (请求参数说明) {int} id  订单id
     * @apiParam (请求参数说明) {String} remark  取消理由
     * @apiSuccessExample {json} 返回样例:
     *{"msg":"ok","errorCode":0}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     */
    public function miniCancel()
    {
        $params = $this->request->param();
        (new OrderService())->miniCancel($params);
        return json(new SuccessMessage());
    }

    /**
     * @api {POST} /api/v1/order/push/handel  Android司机端-接单/拒单
     * @apiGroup   Android
     * @apiVersion 1.0.1
     * @apiDescription   Android司机端-接单/拒单
     * @apiExample {post}  请求样例:
     *    {
     *       "p_id": 1,
     *       "type":2
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

    /**
     * @api {POST} /api/v1/order/begin  Android司机端-开始出发
     * @apiGroup   Android
     * @apiVersion 1.0.1
     * @apiDescription   Android司机端-开始出发
     * @apiExample {post}  请求样例:
     *    {
     *       "id": 1
     *     }
     * @apiParam (请求参数说明) {int} id  订单id
     * @apiSuccessExample {json} 返回样例:
     *{"msg":"ok","errorCode":0}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     */
    public function orderBegin()
    {
        $params = $this->request->param();
        (new OrderService())->orderBegin($params);
        return json(new SuccessMessage());

    }

    /**
     * @api {GET} /api/v1/orders/mini 小程序端-获取订单列表
     * @apiGroup  MINI
     * @apiVersion 1.0.1
     * @apiDescription   小程序端-获取订单列表
     * @apiExample {get}  请求样例:
     * https://tonglingok.com/api/v1/orders/mini
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":1,"per_page":10,"current_page":1,"last_page":1,"data":[{"id":1,"start":"长江路","end":"高速地产","state":1,"create_time":"2019-07-11 01:30:00"}]}}
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} id 订单id
     * @apiSuccess (返回参数说明) {String} start 出发点
     * @apiSuccess (返回参数说明) {String} end 目的地
     * @apiSuccess (返回参数说明) {String}  create_time 创建时间
     * @apiSuccess (返回参数说明) {int} state 订单状态：1 | 未接单；2 | 已接单；4 | 完成；
     */
    public function miniOrders($page = 1, $size = 10)
    {
        $orders = (new OrderService())->miniOrders($page, $size);
        return json(new SuccessMessageWithData(['data' => $orders]));
    }

    public function miniOrder()
    {
        $id = $this->request->param('id');
        $order = (new OrderService())->miniOrder($id);
        return json(new SuccessMessageWithData(['data' => $order]));
    }




}