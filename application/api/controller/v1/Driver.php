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
    /**
     * @api {POST} /api/v1/drive/bind  Android绑定司机账号与websocket通讯关系
     * @apiGroup   Android
     * @apiVersion 1.0.1
     * @apiDescription  后台用户登录
     * @apiExample {post}  请求样例:
     *    {
     *       "client_id": "7f0000010a8f00000002",
     *     }
     * @apiParam (请求参数说明) {String} client_id  司机端和后台建立websocket通信时返回的唯一标识
     * @apiSuccessExample {json} 返回样例:
     *{"msg":"ok","errorCode":0}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     */
    public function bind()
    {
        $params = $this->request->param();
        $client_id = $params['client_id'];
        $u_id = \app\api\service\Token::getCurrentUid();
        Gateway::bindUid($client_id, $u_id);
        return json(new SuccessMessage());

    }

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
     * https://tonglingok.com/api/v1/notices/cms?time_begin=2019-06-28&time_end=2019-06-29&page=1&size=10&online=1&account="a"&username="占三"
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
     * {"msg": "ok","error_code": 0}
     * @apiSuccess (返回参数说明) {int} error_code 错误代码 0 表示没有错误
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

}