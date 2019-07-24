<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\model\DriverT;
use app\api\service\DriverService;
use app\lib\exception\SuccessMessage;
use app\lib\exception\SuccessMessageWithData;
use app\lib\exception\UpdateException;
use GatewayClient\Gateway;

class Driver extends BaseController
{

    public function send()
    {
        $u_id = \app\api\service\Token::getCurrentUid();
        Gateway::sendToUid($u_id, json_encode(['name' => 'hello zml']));
    }

    /**
     * @api {POST} /api/v1/drive/save CMS管理端-新增司机
     * @apiGroup   CMS
     * @apiVersion 1.0.1
     * @apiDescription   CMS管理端-新增司机
     * @apiExample {post}  请求样例:
     *    {
     *       "username": "张三",
     *       "account": "账号",
     *       "phone": "18956225230",
     *       "pwd": "a111111",
     *     }
     * @apiParam (请求参数说明) {String} username  司机名称
     * @apiParam (请求参数说明) {String} account  司机账号
     * @apiParam (请求参数说明) {String} phone  手机号码
     * @apiParam (请求参数说明) {String} pwd  密码
     * @apiSuccessExample {json} 返回样例:
     *{"msg":"ok","errorCode":0}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     */
    public function save()
    {
        $params = $this->request->param();
        (new DriverService())->save($params);
        return json(new SuccessMessage());
    }

    /**
     * @api {GET} /api/v1/drivers CMS管理端-获取司机列表
     * @apiGroup  CMS
     * @apiVersion 1.0.1
     * @apiDescription CMS管理端-获取司机列表
     * @apiExample {get}  请求样例:
     * https://tonglingok.com/api/v1/drivers/cms?time_begin=2019-06-28&time_end=2019-06-29&page=1&size=10&online=1&account="a"&username="占三"
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {String} time_begin 查询开始时间
     * @apiParam (请求参数说明) {String} time_end 查询开始时间
     * @apiParam (请求参数说明) {String} online 启用状态：1 | 在线；2 | 下线;3 | 不限制
     * @apiParam (请求参数说明) {String} username  司机名称
     * @apiParam (请求参数说明) {String} account 账号
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":1,"per_page":10,"current_page":1,"last_page":1,"data":[{"d_id":1,"account":"18956225230","username":"朱明良","phone":"18956225230","money":100,"state":1,"create_time":"2019-06-26 23:50:08"}]}}
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} d_id 司机id
     * @apiSuccess (返回参数说明) {String} account 司机账号
     * @apiSuccess (返回参数说明) {String} username  司机名称
     * @apiSuccess (返回参数说明) {String} phone司机手机号
     * @apiSuccess (返回参数说明) {Float} money 账号金额
     * @apiSuccess (返回参数说明) {int} state 状态：1 | 正常；2 | 停用
     * @apiSuccess (返回参数说明) {int} online 状态：1 | 正常；2 | 停用
     * @apiSuccess (返回参数说明) {String} create_time 创建时间
     */
    public function drivers($page = 1, $size = 10, $time_begin = '', $time_end = '', $username = '', $account = '', $online = 3)
    {
        $drivers = (new DriverService())->drivers($page, $size, $time_begin, $time_end, $username, $account, $online);
        return json(new SuccessMessageWithData(['data' => $drivers]));

    }

    /**
     * @api {POST} /api/v1/driver/handel CMS管理端-修改司机状态(停用/启用)
     * @apiGroup   CMS
     * @apiVersion 1.0.1
     * @apiDescription  CMS管理端-修改司机状态(停用/启用)
     * @apiExample {POST}  请求样例:
     * {
     * "id": 1,
     * "state":2
     * }
     * @apiParam (请求参数说明) {int} d_id 司机ID
     * @apiParam (请求参数说明) {int} state 状态：1 | 正常；2 | 停用
     * @apiSuccessExample {json} 返回样例:
     * {"msg": "ok","errorCode": 0}
     * @apiSuccess (返回参数说明) {int} errorCode 错误代码 0 表示没有错误
     * @apiSuccess (返回参数说明) {String} msg 操作结果描述
     *
     */
    public function handel()
    {
        $params = $this->request->param();
        $id = DriverT::update(['state' => $params['state']], ['id' => $params['d_id']]);
        if (!$id) {
            throw new UpdateException();
        }
        return json(new SuccessMessage());

    }

    /**
     * @api {POST} /api/v1/driver/online Android司机端-司机上下线状态操作(上线/下线)
     * @apiGroup   Android
     * @apiVersion 1.0.1
     * @apiDescription  Android司机端-司机上下线状态操作(上线/下线)
     * @apiExample {POST}  请求样例:
     * {
     * "online":2
     * }
     * @apiParam (请求参数说明) {int} online 上下线状态：1 | 上线；2 | 下线
     * @apiSuccessExample {json} 返回样例:
     * {"msg": "ok","errorCode": 0}
     * @apiSuccess (返回参数说明) {int} errorCode 错误代码 0 表示没有错误
     * @apiSuccess (返回参数说明) {String} msg 操作结果描述
     *
     */
    public function online()
    {
        $params = $this->request->param();

        (new DriverService())->online($params);

        return json(new SuccessMessage());
    }

    /**
     * @api {GET} /api/v1/driver/acceptableOrder Android司机端/Android管理端-转单时获取当前可接单司机列表或者管理端自主建单选择司机
     * @apiGroup  Android
     * @apiVersion 1.0.1
     * @apiDescription Android司机端/Android管理端-转单时获取当前可接单司机列表或者管理端自主建单选择司机
     * @apiExample {get}  请求样例:
     * https://tonglingok.com/api/v1/driver/acceptableOrder?id=1
     * @apiParam (请求参数说明) {int} id 需转单订单id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":[{"id":"5","distance":"1351.4590","name":"zml5","phone":"18825999683","location":["103.90176326036453247","31.60486909089710394"]},{"id":"3","distance":"553.8218","name":"zml3","phone":"18825999681","location":["114.1738244891166687","27.47456377424472151"]},{"id":"2","distance":"1939.1864","name":"zml2","phone":"18825999680","location":["115.05623370409011841","39.94893288365195616"]},{"id":"1","distance":"2017.0671","name":"朱明良","phone":"18956225230","location":["115.79384654760360718","40.58445845049069334"]}]}
     * @apiSuccess (返回参数说明) {int} id 司机ID
     * @apiSuccess (返回参数说明) {String} name 司机姓名
     * @apiSuccess (返回参数说明) {String} phone 司机手机号
     * @apiSuccess (返回参数说明) {Float} distance 司机离出发位置距离
     * @apiSuccess (返回参数说明) {Obj} location 司机实时地理位置：经度-纬度
     */
    public function acceptableOrder()
    {
        $o_id = $this->request->param('id');
        $drivers = (new DriverService())->acceptableOrder($o_id);
        return json(new SuccessMessageWithData(['data' => $drivers]));

    }

    /**
     * @api {GET} /api/v1/drivers/nearby  Android司机端/Android管理端-获取附近司机列表
     * @apiGroup  Android
     * @apiVersion 1.0.1
     * @apiDescription   Android司机端/Android管理端-获取附近司机列表
     * @apiExample {get}  请求样例:
     * https://tonglingok.com/api/v1/drivers/nearby
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":[{"id":"1","state":1,"location":{"lng":"115.79384654760360718","lat":"40.58445845049069334"},"username":"朱明良"}]}
     * @apiSuccess (返回参数说明) {int} id 司机ID
     * @apiSuccess (返回参数说明) {int} state 司机状态：1 | 可接单；2 | 不可接单
     * @apiSuccess (返回参数说明) {String} username 司机姓名
     * @apiSuccess (返回参数说明) {Obj} location 司机实时地理位置
     * @apiSuccess (返回参数说明) {String} lng 司机实时地理位置-经度
     * @apiSuccess (返回参数说明) {String} lat 司机实时地理位置-纬度
     */
    public function nearbyDrivers()
    {
        $drivers = (new DriverService())->nearbyDrivers();
        return json(new SuccessMessageWithData(['data' => $drivers]));

    }

    /**
     * @api {GET} /api/v1/driver/online/records CMS管理端-获取司机在线统计列表
     * @apiGroup  CMS
     * @apiVersion 1.0.1
     * @apiDescription CMS管理端-获取司机在线统计列表
     * @apiExample {get}  请求样例:
     * https://tonglingok.com/api/v1/driver/online/records?time_begin=2019-06-28&time_end=2019-06-29&page=1&size=10&online=1&account="a"&driver="占三"
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {String} time_begin 查询开始时间
     * @apiParam (请求参数说明) {String} time_end 查询开始时间
     * @apiParam (请求参数说明) {String} online 启用状态：1 | 在线；2 | 下线;3 | 不限制
     * @apiParam (请求参数说明) {String} username  司机名称
     * @apiParam (请求参数说明) {String} account 账号
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":2,"per_page":10,"current_page":1,"last_page":1,"data":[{"id":2,"account":"18956225230","username":"朱明良","phone":"18956225230","count":0,"money":0,"online_time":4,"online":2,"last_online_time":"2019-07-20 15:52:17","create_time":"2019-07-20 15:52:21"},{"id":1,"account":"18956225230","username":"朱明良","phone":"18956225230","count":0,"money":0,"online_time":3372,"online":2,"last_online_time":"2019-07-20 15:52:17","create_time":"2019-07-20 15:50:42"}]}}
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} id 记录id
     * @apiSuccess (返回参数说明) {String} account 司机账号
     * @apiSuccess (返回参数说明) {String} username  司机名称
     * @apiSuccess (返回参数说明) {String} phone司机手机号
     * @apiSuccess (返回参数说明) {int} count 任务数
     * @apiSuccess (返回参数说明) {Float} money 收入总计
     * @apiSuccess (返回参数说明) {int} online 状态：1 | 正常；2 | 停用
     * @apiSuccess (返回参数说明) {int} online_time 在线时长：单位秒
     * @apiSuccess (返回参数说明) {String} last_online_time 上线时间
     */
    public function onlineRecords($page = 1, $size = 10, $time_begin = '', $time_end = '', $online = 3, $driver = '', $account = '')
    {
        $list = (new DriverService())->onlineRecord($page, $size, $time_begin, $time_end, $online, $driver, $account);

        return json(new SuccessMessageWithData(['data' => $list]));
    }


    /**
     * @api {GET} /api/v1/driver/order/check Android司机端-检测是否有未完成的订单，有则返回数据
     * @apiGroup  Android
     * @apiVersion 1.0.1
     * @apiDescription    Android司机端-检测是否有未完成的订单，有则返回数据
     * @apiExample {get}  请求样例:
     * https://tonglingok.com/api/v1/driver/order/check
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"id":10,"state":2,"start":"肯德基(宁德万达餐厅)","end":"肯德基(蕉城餐厅)","begin":2,"name":"李师傅","phone":"18814184025","create_time":"2019-07-23 16:31:35","arriving_time":null,"receive_time":null,"start_lng":"119.54611","start_lat":"26.658681","end_lng":"119.531554","end_lat":"26.666172"}}
     * @apiSuccess (返回参数说明) {int}  id 订单id
     * @apiSuccess (返回参数说明) {String}  start起点
     * @apiSuccess (返回参数说明) {String}  end 目的地
     * @apiSuccess (返回参数说明) {String}  name 乘客名称
     * @apiSuccess (返回参数说明) {String}  phone 乘客手机号
     * @apiSuccess (返回参数说明) {String}  end 目的地
     * @apiSuccess (返回参数说明) {String}  create_time 订单创建时间
     * @apiSuccess (返回参数说明) {int}  state 订单状态
     * @apiSuccess (返回参数说明) {String} start_lat  出发地纬度
     * @apiSuccess (返回参数说明) {String} start_lng  出发地经度
     * @apiSuccess (返回参数说明) {String} end_lat  目的地纬度
     * @apiSuccess (返回参数说明) {String} end_lng  目的地经度
     */
    public function checkDriverHasUnCompleteOrder()
    {
        $info = (new DriverService())->checkDriverHasUnCompleteOrder();
        return json(new SuccessMessageWithData(['data' => $info]));
    }


}