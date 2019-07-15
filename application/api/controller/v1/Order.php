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
        return json(new SuccessMessageWithData(['data' => ['id' => $id]]));
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
     * @api {POST} /api/v1/order/mini/cancel 小程序端/Android司机端/Android管理端-撤销订单
     * @apiGroup   COMMON
     * @apiVersion 1.0.1
     * @apiDescription   小程序端/Android司机端/Android管理端-撤销订单
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
    public function orderCancel()
    {
        $params = $this->request->param();
        (new OrderService())->orderCancel($params);
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
     *       "type":2,
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
     * @api {POST} /api/v1/order/arriving  Android司机端-点击到达起点
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
    public function orderArriving()
    {
        $params = $this->request->param();
        (new OrderService())->arrivingStart($params);
        return json(new SuccessMessage());

    }

    /**
     * @api {GET} /api/v1/orders/mini 小程序端-获取订单列表
     * @apiGroup  MINI
     * @apiVersion 1.0.1
     * @apiDescription   小程序端-获取订单列表
     * @apiExample {get}  请求样例:
     * https://tonglingok.com/api/v1/orders/mini?page=1&size=10
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
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

    /**
     * @api {GET} /api/v1/order/mini 小程序端-获取订单信息
     * @apiGroup  MINI
     * @apiVersion 1.0.1
     * @apiDescription   小程序端-获取订单信息
     * @apiExample {get}  请求样例:
     * https://tonglingok.com/api/v1/order/mini?id=1
     * @apiParam (请求参数说明) {int} id 订单id
     * @apiSuccessExample {json} 订单未被接单返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"state":1}}
     * @apiSuccess (返回参数说明) {int} state 订单状态：1 | 未接单
     * @apiSuccessExample {json} 订单已被接单但是未完成订单返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"state":2,"driver":"朱明良","phone":"18956225230","start":"长江路","begin":2,"arriving_time":"2019-07-14 10:00:00","receive_time":"2019-07-14 10:00:10","driver_lng":"115.79384654760360718","driver_lat":"40.58445845049069334"}}
     * @apiSuccess (返回参数说明) {String} driver 司机名称
     * @apiSuccess (返回参数说明) {String} phone 司机手机号
     * @apiSuccess (返回参数说明) {String} start 出发点
     * @apiSuccess (返回参数说明) {String} begin 司机是否点击开始出发：1-是；2-否
     * @apiSuccess (返回参数说明) {String} arriving_time 到达起点时间
     * @apiSuccess (返回参数说明) {String} receive_time 司机接单时间
     * @apiSuccess (返回参数说明) {String} driver_lng 司机当前位置经度
     * @apiSuccess (返回参数说明) {String} driver_lat 司机当前位置纬度
     * @apiSuccessExample {json} 订单已完成返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"state":4,"distance":0,"money":0,"far_distance":0,"far_money":0,"ticket_money":0,"wait_time":0,"wait_money":0,"weather_money":0}}
     * @apiSuccess (返回参数说明) {int}  state 订单状态：4-已完成
     * @apiSuccess (返回参数说明) {int}  distance  司机行驶路径距离
     * @apiSuccess (返回参数说明) {int}  distance_money  司机行驶路径距离产生金额
     * @apiSuccess (返回参数说明) {int}  money 订单金额
     * @apiSuccess (返回参数说明) {int}  far_distance 远程接驾距离
     * @apiSuccess (返回参数说明) {int}  far_money 远程接驾金额
     * @apiSuccess (返回参数说明) {int}  ticket_money 使用优惠券金额
     * @apiSuccess (返回参数说明) {int}  wait_time  等待时间
     * @apiSuccess (返回参数说明) {int}  wait_money 等待时间金额
     * @apiSuccess (返回参数说明) {int}  weather_money 恶劣天气补助
     */
    public function miniOrder()
    {
        $id = $this->request->param('id');
        $order = (new OrderService())->miniOrder($id);
        return json(new SuccessMessageWithData(['data' => $order]));
    }

    /**
     * @api {POST} /api/v1/order/driver/complete Android司机端-司机确认订单完成
     * @apiGroup  Android
     * @apiVersion 1.0.1
     * @apiDescription   小程序端-获取订单信息
     * @apiExample {post}  请求样例:
     *    {
     *       "id":1,
     *       "wait_time":360
     *     }
     * @apiParam (请求参数说明) {int} id 订单id
     * @apiParam (请求参数说明) {int} wait_time 等待时间 单位秒
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"state":4,"distance":16,"distance_money":158,"money":202,"far_distance":0,"far_money":0,"ticket_money":5,"wait_time":31,"wait_money":1,"weather_money":48}}
     * @apiSuccess (返回参数说明) {int}  state 订单状态：4-已完成
     * @apiSuccess (返回参数说明) {int}  distance  司机行驶路径距离
     * @apiSuccess (返回参数说明) {int}  distance_money  司机行驶路径距离产生金额
     * @apiSuccess (返回参数说明) {int}  money 订单金额
     * @apiSuccess (返回参数说明) {int}  far_distance 远程接驾距离
     * @apiSuccess (返回参数说明) {int}  far_money 远程接驾金额
     * @apiSuccess (返回参数说明) {int}  ticket_money 使用优惠券金额
     * @apiSuccess (返回参数说明) {int}  wait_time  等待时间
     * @apiSuccess (返回参数说明) {int}  wait_money 等待时间金额
     * @apiSuccess (返回参数说明) {int}  weather_money 恶劣天气补助
     */
    public function orderComplete()
    {
        $params = $this->request->param();
        $order = (new OrderService())->driverCompleteOrder($params);
        return json(new SuccessMessageWithData(['data' => $order]));

    }

    /**
     * @api {POST} /api/v1/order/drive/save  Android司机端-司机自主创建订单
     * @apiGroup   Android
     * @apiVersion 1.0.1
     * @apiDescription   Android司机端-司机自主创建订单
     * @apiExample {post}  请求样例:
     *    {
     *       "start":"铜陵市维也纳大酒店",
     *       "start_lat":"12.1212",
     *       "start_lng":"23.121",
     *       "end":铜陵市高速",
     *       "end_lat":"12.1212",
     *       "end_lng":"21.1212",
     *       "phone":"18956225230",
     *       "name":"詹先生",
     *     }
     * @apiParam (请求参数说明) {String} start  出发地
     * @apiParam (请求参数说明) {String} start_lat  出发地纬度
     * @apiParam (请求参数说明) {String} start_lng  出发地经度
     * @apiParam (请求参数说明) {String} end  目的地
     * @apiParam (请求参数说明) {String} end_lat  目的地纬度
     * @apiParam (请求参数说明) {String} end_lng  目的地经度
     * @apiParam (请求参数说明) {String} phone  手机号
     * @apiParam (请求参数说明) {String} name  乘客姓名
     * @apiSuccessExample {json} 返回样例:
     *{"id":1,"errorCode":0,"data":{"id":1}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {int} id 订单id
     */
    public function saveDriverOrder()
    {
        $params = $this->request->param();
        $o_id = (new OrderService())->saveDriverOrder($params);
        return json(new SuccessMessageWithData(['data' => ['id' => $o_id]]));

    }

    /**
     * @api {POST} /api/v1/order/manager/save  Android管理端-管理员机自主创建订单
     * @apiGroup   Android
     * @apiVersion 1.0.1
     * @apiDescription   Android管理端-管理员机自主创建订单
     * @apiExample {post}  请求样例:
     *    {
     *       "start":"铜陵市维也纳大酒店",
     *       "start_lat":"12.1212",
     *       "start_lng":"23.121",
     *       "end":铜陵市高速",
     *       "end_lat":"12.1212",
     *       "end_lng":"21.1212",
     *       "phone":"18956225230",
     *       "name":"詹先生",
     *       "d_id":1,
     *       "type":1
     *     }
     * @apiParam (请求参数说明) {String} start  出发地
     * @apiParam (请求参数说明) {String} start_lat  出发地纬度
     * @apiParam (请求参数说明) {String} start_lng  出发地经度
     * @apiParam (请求参数说明) {String} end  目的地
     * @apiParam (请求参数说明) {String} end_lat  目的地纬度
     * @apiParam (请求参数说明) {String} end_lng  目的地经度
     * @apiParam (请求参数说明) {String} phone  手机号
     * @apiParam (请求参数说明) {String} name  乘客姓名
     * @apiParam (请求参数说明) {int} d_id  司机id
     * @apiParam (请求参数说明) {int} type  订单金额类别：1|非固定金额订单；2|固定金额订单
     * @apiSuccessExample {json} 返回样例:
     *{"id":1,"errorCode":0,"data":{"id":1}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {int} id 订单id
     */
    public function saveManagerOrder()
    {
        $params = $this->request->param();
        $o_id = (new OrderService())->saveManagerOrder($params);
        return json(new SuccessMessageWithData(['data' => ['id' => $o_id]]));
    }

    /**
     * @api {POST} /api/v1/order/transferOrder  Android司机端-转单
     * @apiGroup   Android
     * @apiVersion 1.0.1
     * @apiDescription   Android司机端-转单
     * @apiExample {post}  请求样例:
     *    {
     *       "id": 1,
     *       "d_id":1,
     *     }
     * @apiParam (请求参数说明) {int} id  订单id
     * @apiParam (请求参数说明) {int} d_id  被转单司机id
     * @apiSuccessExample {json} 返回样例:
     *{"msg":"ok","errorCode":0}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     */
    public function transferOrder()
    {
        $params = $this->request->param();
        (new OrderService())->transferOrder($params);
        return json(new SuccessMessage());

    }

    public function choiceDriverByManager($params)
    {
        $params = $this->request->param();
        (new OrderService())->choiceDriverByManager($params);
        return json(new SuccessMessage());

    }

}