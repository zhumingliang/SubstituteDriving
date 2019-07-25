<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\LogService;
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
     *{"msg":"ok","errorCode":0,"data":{"id":1}}
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
        return json(new SuccessMessage());
    }

    /**
     * 处理推送信息
     */
    public function handelDriverNoAnswer()
    {
        (new OrderService())->handelDriverNoAnswer();
        return json(new SuccessMessage());
    }

    /**
     * @api {POST} /api/v1/order/cancel 小程序端/Android司机端/Android管理端-撤销订单
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
     * @apiDescription   Android司机端-司机确认订单完成
     * @apiExample {post}  请求样例:
     *    {
     *       "id":1,
     *       "wait_time":360
     *       "wait_money":10,
     *       "distance":10.01,
     *       "distance_money":30,
     *     }
     * @apiParam (请求参数说明) {int} id 订单id
     * @apiParam (请求参数说明) {int} wait_time 等待时间 单位分，不足一分钟按一分钟算
     * @apiParam (请求参数说明) {Float} wait_money 等待产生费用
     * @apiParam (请求参数说明) {Float} distance 行驶距离
     * @apiParam (请求参数说明) {Float} distance_money 行驶产生金额
     * @apiSuccessExample {json} 返回样例:""
     * {"msg":"ok","errorCode":0,"code":200,"data":{"driver_name":"张司机","":"start","end":"","name":"","phone":"","state":4,"distance":16,"distance_money":158,"money":202,"far_distance":0,"far_money":0,"ticket_money":5,"wait_time":31,"wait_money":1,"weather_money":48}}
     * @apiSuccess (返回参数说明) {String}  driver_name 司机名称
     * @apiSuccess (返回参数说明) {String}  start起点
     * @apiSuccess (返回参数说明) {String}  end 目的地
     * @apiSuccess (返回参数说明) {String}  name用户名称
     * @apiSuccess (返回参数说明) {String}  phone 用户手机号
     * @apiSuccess (返回参数说明) {String}  create_time 订单创建时间
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
     *{"msg":"ok","errorCode":0,"data":{"id":1}}
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
     *       "type":1,
     *       "money":1000,
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
     * @apiParam (请求参数说明) {int} money 固定金额 非固定金额订单为0
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
     * @api {POST} /api/v1/order/transfer  Android司机端/Android管理端-转单
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

    /**
     * @api {POST} /api/v1/order/transferOrder/manager  Android管理端-分配司机订单
     * @apiGroup   Android
     * @apiVersion 1.0.1
     * @apiDescription    Android管理端-分配司机订单
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
    public function choiceDriverByManager()
    {
        $params = $this->request->param();
        (new OrderService())->choiceDriverByManager($params);
        return json(new SuccessMessage());

    }

    /**
     * @api {GET} /api/v1/orders/driver Android司机端-获取订单列表
     * @apiGroup  Android
     * @apiVersion 1.0.1
     * @apiDescription   Android司机端-获取订单列表
     * @apiExample {get}  请求样例:
     * https://tonglingok.com/api/v1/orders/driver?page=1&size=10&driver=''&time_begin=''&time_end=''
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {int} time_begin 查询开始时间
     * @apiParam (请求参数说明) {int} time_end 查询结束时间
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":1,"per_page":10,"current_page":1,"last_page":1,"data":[{"id":1,"d_id":1,"superior":null,"transfer":2,"from":"小程序下单","state":4,"start":"长江路","end":"高速地产","name":"","money":136,"cancel_type":null,"cancel_remark":null,"create_time":"2019-07-11 01:30:00"}],"statistic":{"orders_count":1,"all_money":141,"ticket_money":5}}}
     * @apiSuccess (返回参数说明) {Obj} orders 订单列表
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} id 订单id
     * @apiSuccess (返回参数说明) {int} d_id 司机ID
     * @apiSuccess (返回参数说明) {int} transfer 是否为转单订单：1|是；2|否
     * @apiSuccess (返回参数说明) {Obj} superior 转单上级信息
     * @apiSuccess (返回参数说明) {String} superior-username 上级司机姓名
     * @apiSuccess (返回参数说明) {String} start 出发点
     * @apiSuccess (返回参数说明) {String} end 目的地
     * @apiSuccess (返回参数说明) {String} name 乘客姓名
     * @apiSuccess (返回参数说明) {Float} money 订单金额
     * @apiSuccess (返回参数说明) {String} cancel_type 撤销订单者类别：乘客/司机/管理员
     * @apiSuccess (返回参数说明) {String} cancel_remark 撤销订单说明
     * @apiSuccess (返回参数说明) {String} from 下单来源
     * @apiSuccess (返回参数说明) {String}  create_time 创建时间
     * @apiSuccess (返回参数说明) {int} state 订单状态：4|完成；5|撤销
     * @apiSuccess (返回参数说明) {Obj} statistic 统计信息
     * @apiSuccess (返回参数说明) {int} statistic-orders_count 订单数
     * @apiSuccess (返回参数说明) {Float} statistic-all_money 订单总金额
     * @apiSuccess (返回参数说明) {Float} statistic-ticket_money 使用优惠券总金额s
     */
    public function driverOrders($page = 1, $size = 10, $time_begin = '', $time_end = '')
    {
        $orders = (new OrderService())->driverOrders($page, $size, $time_begin, $time_end);
        return json(new SuccessMessageWithData(['data' => $orders]));
    }

    /**
     * @api {GET} /api/v1/order/info Android司机端/Android管理端/CMS管理端-获取订单详情
     * @apiGroup  COMMON
     * @apiVersion 1.0.1
     * @apiDescription   Android司机端/Android管理端/CMS管理端-获取订单详情
     * @apiExample {get}  请求样例:
     * https://tonglingok.com/api/v1/order/info?id=1
     * @apiParam (请求参数说明) {int} id 订单id
     * @apiSuccessExample {json} 订单未被接单返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"driver_name":"张司机","":"start","end":"","name":"","phone":"","state":4,"distance":16,"distance_money":158,"money":202,"far_distance":0,"far_money":0,"ticket_money":5,"wait_time":31,"wait_money":1,"weather_money":48}}
     * @apiSuccess (返回参数说明) {String}  driver_name 司机名称
     * @apiSuccess (返回参数说明) {String}  start起点
     * @apiSuccess (返回参数说明) {String}  end 目的地
     * @apiSuccess (返回参数说明) {String}  name用户名称
     * @apiSuccess (返回参数说明) {String}  phone 用户手机号
     * @apiSuccess (返回参数说明) {String}  create_time 订单创建时间
     * @apiSuccess (返回参数说明) {int}  state 订单状态：4-已完成,5-撤销
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
    public function orderInfo()
    {
        $id = $this->request->param('id');
        $order = (new OrderService())->orderInfo($id);
        return json(new SuccessMessageWithData(['data' => $order]));
    }

    /**
     * @api {GET} /api/v1/orders/manager Android管理端-获取订单列表
     * @apiGroup  Android
     * @apiVersion 1.0.1
     * @apiDescription   Android管理端-获取订单列表
     * @apiExample {get}  请求样例:
     * https://tonglingok.com/api/v1/orders/manager?page=1&size=10&driver=''&time_begin=''&time_end=''
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {int} driver 司机名称
     * @apiParam (请求参数说明) {int} time_begin 查询开始时间
     * @apiParam (请求参数说明) {int} time_end 查询结束时间
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":6,"per_page":10,"current_page":1,"last_page":1,"data":[{"id":8,"d_id":0,"superior":{"username":"zml2"},"transfer":1,"from":"小程序下单","state":5,"start":"安徽省铜陵市郊区铜都大道北段","end":"铜陵站","name":"先生\/女士","money":0,"cancel_type":"乘客","cancel_remark":"司机不接电话，","create_time":"2019-07-17 20:15:01"},{"id":6,"d_id":0,"superior":null,"transfer":2,"from":"小程序下单","state":5,"start":"安徽省铜陵市铜官区谢垅路","end":"东山苑小区","name":"先生\/女士","money":0,"cancel_type":"乘客","cancel_remark":"等待司机太久，","create_time":"2019-07-17 16:08:39"},{"id":5,"d_id":0,"superior":null,"transfer":2,"from":"小程序下单","state":5,"start":"安徽省铜陵市铜官区谢垅路","end":"东山苑小区","name":"先生\/女士","money":0,"cancel_type":"乘客","cancel_remark":"没有司机接单，","create_time":"2019-07-17 15:45:57"},{"id":3,"d_id":0,"superior":null,"transfer":2,"from":"小程序下单","state":5,"start":"广东省江门市蓬江区建设二路18号","end":"蓬江区人民政府","name":"","money":0,"cancel_type":"乘客","cancel_remark":"没有司机接单，","create_time":"2019-07-14 23:41:49"},{"id":2,"d_id":0,"superior":null,"transfer":2,"from":"小程序下单","state":4,"start":"广东省江门市蓬江区建设二路18号","end":"蓬江区人民政府","name":"","money":80,"cancel_type":"乘客","cancel_remark":"没有司机接单，","create_time":"2019-07-14 23:37:23"},{"id":1,"d_id":1,"superior":null,"transfer":2,"from":"小程序下单","state":4,"start":"长江路","end":"高速地产","name":"","money":136,"cancel_type":null,"cancel_remark":null,"create_time":"2019-07-11 01:30:00"}],"statistic":{"members":2,"orders_count":2,"all_money":224,"ticket_money":8}}}
     * @apiSuccess (返回参数说明) {Obj} orders 订单列表
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} id 订单id
     * @apiSuccess (返回参数说明) {int} d_id 司机ID
     * @apiSuccess (返回参数说明) {int} transfer 是否为转单订单：1|是；2|否
     * @apiSuccess (返回参数说明) {Obj} superior 转单上级信息
     * @apiSuccess (返回参数说明) {String} superior-username 上级司机姓名
     * @apiSuccess (返回参数说明) {String} start 出发点
     * @apiSuccess (返回参数说明) {String} end 目的地
     * @apiSuccess (返回参数说明) {String} name 乘客姓名
     * @apiSuccess (返回参数说明) {Float} money 订单金额
     * @apiSuccess (返回参数说明) {String} cancel_type 撤销订单者类别：乘客/司机/管理员
     * @apiSuccess (返回参数说明) {String} cancel_remark 撤销订单说明
     * @apiSuccess (返回参数说明) {String} from 下单来源
     * @apiSuccess (返回参数说明) {String}  create_time 创建时间
     * @apiSuccess (返回参数说明) {int} state 订单状态：4|完成；5|撤销
     * @apiSuccess (返回参数说明) {Obj} statistic 统计信息
     * @apiSuccess (返回参数说明) {int} statistic-members 代驾人数
     * @apiSuccess (返回参数说明) {int} statistic-orders_count 订单数
     * @apiSuccess (返回参数说明) {Float} statistic-all_money 订单总金额
     * @apiSuccess (返回参数说明) {Float} statistic-ticket_money 使用优惠券总金额
     */
    public function managerOrders($page = 1, $size = 10, $driver = '', $time_begin = '', $time_end = '')
    {
        $data = (new OrderService())->managerOrders($page, $size, $driver, $time_begin, $time_end);
        return json(new SuccessMessageWithData(['data' => $data]));
    }

    /**
     * @api {GET} /api/v1/order/consumption/records Android管理端/Android司机端-获取用户消费记录
     * @apiGroup  Android
     * @apiVersion 1.0.1
     * @apiDescription    Android管理端/Android司机端-获取用户消费记录
     * @apiExample {get}  请求样例:
     * https://tonglingok.com/api/v1/order/consumption/records?phone=134&page=1&size=5
     * * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {String} phone 用户手机号
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":1,"per_page":5,"current_page":1,"last_page":1,"data":[{"create_time":"2019-07-14 23:37:23","start":"广东省江门市蓬江区建设二路18号","end":"蓬江区人民政府","money":80}],"statistic":{"count":1,"money":80}}}
     * @apiSuccess (返回参数说明) {Obj} orders 订单列表
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {String} start 出发点
     * @apiSuccess (返回参数说明) {String} end 目的地
     * @apiSuccess (返回参数说明) {Float} money 订单金额
     * @apiSuccess (返回参数说明) {Obj} statistic 统计信息
     * @apiSuccess (返回参数说明) {int} statistic-count 订单数
     * @apiSuccess (返回参数说明) {Float} statistic-money 订单总金额
     */
    public function recordsOfConsumption($page = 1, $size = 5)
    {
        $phone = $this->request->param('phone');
        $info = (new OrderService())->recordsOfConsumption($page, $size, $phone);
        return json(new SuccessMessageWithData(['data' => $info]));
    }

    /**
     * @api {GET} /api/v1/order/locations Android管理端/CMS管理端-获取订单地理位置
     * @apiGroup  COMMON
     * @apiVersion 1.0.1
     * @apiDescription   Android管理端/CMS管理端-获取订单地理位置
     * @apiExample {get}  请求样例:
     * https://tonglingok.com/api/v1/order/locations?id=1
     * @apiParam (请求参数说明) {int} id 订单id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"start":"长江路","end":"高速地产","locations":[{"lat":"40.584459","lng":"115.793844"},{"lat":"40.584459","lng":"115.793844"},{"lat":"40.584459","lng":"115.793844"},{"lat":"39.948933","lng":"115.056232"},{"lat":"27.474563","lng":"114.173822"},{"lat":"25.518178","lng":"111.341648"},{"lat":"31.60487","lng":"103.901761"}]}}
     * @apiSuccess (返回参数说明) {String} start 起点
     * @apiSuccess (返回参数说明) {String} end 终点
     * @apiSuccess (返回参数说明) {Obj} locations 地理位置坐标
     * @apiSuccess (返回参数说明) {String} lat 纬度
     * @apiSuccess (返回参数说明) {String} lng 经度
     */
    public function orderLocations()
    {
        $id = $this->request->param('id');
        $locations = (new OrderService())->orderLocations($id);
        return json(new SuccessMessageWithData(['data' => $locations]));
    }

    /**
     * @api {GET} /api/v1/orders/current Android管理端-获取实时订单列表
     * @apiGroup  Android
     * @apiVersion 1.0.1
     * @apiDescription    Android管理端-获取实时订单列表
     * @apiExample {get}  请求样例:
     * https://tonglingok.com/api/v1/orders/current?page=1&size=10
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":2,"per_page":10,"current_page":1,"last_page":1,"data":[{"id":7,"d_id":1,"superior_id":2,"superior":null,"transfer":2,"from":"小程序下单","state":1,"start":"安徽省铜陵市铜官区谢垅路","end":"东山苑小区","begin":2,"name":"先生\/女士","create_time":"2019-07-17 16:12:08","driver":{"id":1,"username":"朱明良"}},{"id":4,"d_id":0,"superior_id":0,"superior":null,"transfer":2,"from":"小程序下单","state":1,"start":"广东省江门市蓬江区建设二路18号","end":"新会万达广场","begin":2,"name":"先生\/女士","create_time":"2019-07-17 02:43:56","driver":null}]}}
     * @apiSuccess (返回参数说明) {Obj} orders 订单列表
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} id 订单id
     * @apiSuccess (返回参数说明) {int} d_id 司机ID
     * @apiSuccess (返回参数说明) {int} transfer 是否为转单订单：1|是；2|否
     * @apiSuccess (返回参数说明) {Obj} superior 转单上级信息
     * @apiSuccess (返回参数说明) {String} superior-username 上级司机姓名
     * @apiSuccess (返回参数说明) {Obj} driver接单司机信息
     * @apiSuccess (返回参数说明) {String} driver-username 司机姓名
     * @apiSuccess (返回参数说明) {String} start 出发点
     * @apiSuccess (返回参数说明) {String} end 目的地
     * @apiSuccess (返回参数说明) {String} name 乘客姓名
     * @apiSuccess (返回参数说明) {int} begin 是否开始出发：1|开始出发；2|未开始出发
     * @apiSuccess (返回参数说明) {String} from 下单来源
     * @apiSuccess (返回参数说明) {String}  create_time 创建时间
     * @apiSuccess (返回参数说明) {int} state 订单状态：1|未派单；2|派单未接单
     */
    public function current($page = 1, $size = 10)
    {
        $orders = (new OrderService())->current($page, $size);
        return json(new SuccessMessageWithData(['data' => $orders]));

    }

    /**
     * @api {POST} /api/v1/order/withdraw  Android管理端-撤回没有开始出发的订单（未接单/接单未出发）
     * @apiGroup   Android
     * @apiVersion 1.0.1
     * @apiDescription   Android管理端-撤回没有开始出发的订单（未接单/接单未出发）
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
    public function withdraw()
    {
        $id = $this->request->param('id');
        (new OrderService())->withdraw($id);
        return json(new SuccessMessage());
    }

    /**
     * @api {GET} /api/v1/orders/manager/cms CMS管理端-获取订单列表
     * @apiGroup  CMS
     * @apiVersion 1.0.1
     * @apiDescription   CMS管理端-获取订单列表
     * @apiExample {get}  请求样例:
     * https://tonglingok.com/api/v1/orders/manager/cms?page=1&size=10&driver=''&time_begin=''&time_end=''&order_state=1&order_from=1
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {int} driver 司机名称
     * @apiParam (请求参数说明) {int} time_begin 查询开始时间
     * @apiParam (请求参数说明) {int} time_end 查询结束时间
     * @apiParam (请求参数说明) {int} order_state 订单状态：1|未接单；2|已接单；4|完成；5|已经撤销；6|全部
     * @apiParam (请求参数说明) {int} order_from 订单来源：1|小程序下单；2|司机自主建单;3|管理员自主建单；4|公众号下单；5|全部
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":8,"per_page":10,"current_page":1,"last_page":1,"data":[{"id":8,"d_id":1,"from":"小程序下单","driver":"朱明良","money":0,"state":5,"create_time":"2019-07-17 20:15:01","name":"先生\/女士","phone":"18956225230"},{"id":7,"d_id":1,"from":"小程序下单","driver":"朱明良","money":0,"state":1,"create_time":"2019-07-17 16:12:08","name":"先生\/女士","phone":"1"},{"id":6,"d_id":1,"from":"小程序下单","driver":"朱明良","money":0,"state":5,"create_time":"2019-07-17 16:08:39","name":"先生\/女士","phone":"18956225230"},{"id":5,"d_id":1,"from":"小程序下单","driver":"朱明良","money":0,"state":5,"create_time":"2019-07-17 15:45:57","name":"先生\/女士","phone":"18956225230"},{"id":4,"d_id":0,"from":"小程序下单","driver":null,"money":0,"state":1,"create_time":"2019-07-17 02:43:56","name":"先生\/女士","phone":"134"},{"id":3,"d_id":0,"from":"小程序下单","driver":null,"money":0,"state":5,"create_time":"2019-07-14 23:41:49","name":"","phone":"134"},{"id":2,"d_id":0,"from":"小程序下单","driver":null,"money":80,"state":4,"create_time":"2019-07-14 23:37:23","name":"","phone":"134"},{"id":1,"d_id":1,"from":"小程序下单","driver":"朱明良","money":136,"state":4,"create_time":"2019-07-11 01:30:00","name":"","phone":""}],"statistic":{"members":2,"orders_count":2,"all_money":224,"ticket_money":8}}}
     * @apiSuccess (返回参数说明) {Obj} orders 订单列表
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} id 订单id
     * @apiSuccess (返回参数说明) {int} d_id 司机ID
     * @apiSuccess (返回参数说明) {String} driver 司机名称
     * @apiSuccess (返回参数说明) {String} name 乘客姓名
     * @apiSuccess (返回参数说明) {String} phone 乘客手机号
     * @apiSuccess (返回参数说明) {Float} money 订单金额
     * @apiSuccess (返回参数说明) {String} from 下单来源
     * @apiSuccess (返回参数说明) {String}  create_time 创建时间
     * @apiSuccess (返回参数说明) {int} state 订单状态：1|未接单；2|已接单；4|完成；5|已经撤销
     * @apiSuccess (返回参数说明) {Obj} statistic 统计信息
     * @apiSuccess (返回参数说明) {int} statistic-members 代驾人数
     * @apiSuccess (返回参数说明) {int} statistic-orders_count 订单数
     * @apiSuccess (返回参数说明) {Float} statistic-all_money 订单总金额
     * @apiSuccess (返回参数说明) {Float} statistic-ticket_money 使用优惠券总金额
     */
    public function CMSManagerOrders($page = 1, $size = 10, $driver = '', $time_begin = '', $time_end = '', $order_state = 6, $order_from = 5)
    {
        $data = (new OrderService())->CMSManagerOrders($page, $size, $driver, $time_begin, $time_end, $order_state, $order_from);
        return json(new SuccessMessageWithData(['data' => $data]));
    }

    /**
     * @api {GET} /api/v1/orders/insurance/cms CMS管理端-保险公司-获取订单列表
     * @apiGroup  CMS
     * @apiVersion 1.0.1
     * @apiDescription  CMS管理端-保险公司-获取订单列表
     * @apiExample {get}  请求样例:
     * https://tonglingok.com/api/v1/orders/insurance/cms?page=1&size=10&time_begin=''&time_end=''
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {int} driver 司机名称
     * @apiParam (请求参数说明) {int} time_begin 查询开始时间
     * @apiParam (请求参数说明) {int} time_end 查询结束时间
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":2,"per_page":10,"current_page":1,"last_page":1,"data":[{"id":2,"d_id":0,"from":"小程序下单","driver":null,"money":80,"state":4,"create_time":"2019-07-14 23:37:23","name":""},{"id":1,"d_id":1,"from":"小程序下单","driver":"朱明良","money":136,"state":4,"create_time":"2019-07-11 01:30:00","name":""}],"statistic":2}}
     * @apiSuccess (返回参数说明) {Obj} orders 订单列表
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} id 订单id
     * @apiSuccess (返回参数说明) {int} d_id 司机ID
     * @apiSuccess (返回参数说明) {String} driver 司机名称
     * @apiSuccess (返回参数说明) {String} name 乘客姓名
     * @apiSuccess (返回参数说明) {Float} money 订单金额
     * @apiSuccess (返回参数说明) {String} from 下单来源
     * @apiSuccess (返回参数说明) {String}  create_time 创建时间
     * @apiSuccess (返回参数说明) {int} state 订单状态：1|未接单；2|已接单；4|完成；5|已经撤销
     * @apiSuccess (返回参数说明) {int} statistic 订单数
     */
    public function CMSInsuranceOrders($page = 1, $size = 10, $time_begin = '', $time_end = '')
    {
        $data = (new OrderService())->CMSInsuranceOrders($page, $size, $time_begin, $time_end);
        return json(new SuccessMessageWithData(['data' => $data]));
    }

}