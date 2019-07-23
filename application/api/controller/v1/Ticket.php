<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\model\CategoryT;
use app\api\model\TicketT;
use app\api\service\TicketService;
use app\lib\enum\CommonEnum;
use app\lib\exception\SaveException;
use app\lib\exception\SuccessMessage;
use app\lib\exception\SuccessMessageWithData;
use app\lib\exception\UpdateException;

class Ticket extends BaseController
{
    /**
     * @api {POST} /api/v1/ticket/save  Android管理端/CMS管理端-新增优惠券
     * @apiGroup  COMMON
     * @apiVersion 1.0.1
     * @apiDescription  Android管理端/CMS管理端-新增优惠券
     * @apiExample {post}  请求样例:
     *    {
     *       "name": "新用户优惠券",
     *       "price": 5,
     *       "time_begin": "2019-10-01",
     *       "time_end":"2019-10-08",
     *       "count":500,
     *       "scene": 1,
     *       "source": 1,
     *     }
     * @apiParam (请求参数说明) {String} name    优惠券名称
     * @apiParam (请求参数说明) {int} price   优惠券金额：单位元
     * @apiParam (请求参数说明) {String} time_begin  有效开始时间
     * @apiParam (请求参数说明) {String} time_end   有效结束时间
     * @apiParam (请求参数说明) {String} count   优惠券数量
     * @apiParam (请求参数说明) {int} scene   应用场景（暂定）：1 | 新用户优惠券
     * @apiParam (请求参数说明) {int} source   操作来源：1 | CMS；2 | Android
     *
     * @apiSuccessExample {json} 返回样例:
     *{"msg":"ok","errorCode":0}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     */
    public function save()
    {
        $params = $this->request->param();
        $params['state'] = CommonEnum::STATE_IS_OK;
        $params['u_id'] = \app\api\service\Token::getCurrentUid();
        $ticket = TicketT::create($params);
        if (!$ticket) {
            throw new SaveException();
        }
        return json(new SuccessMessage());

    }

    /**
     * @api {POST} /api/v1/ticket/update  Android管理端/CMS管理端-编辑优惠券
     * @apiGroup  COMMON
     * @apiVersion 1.0.1
     * @apiDescription  Android管理端/CMS管理端-编辑优惠券
     * @apiExample {post}  请求样例:
     *    {
     *       "id":1,
     *       "name": "新用户优惠券",
     *       "price": 5,
     *       "time_begin": "2019-10-01",
     *       "time_end":"2019-10-08",
     *       "count":500,
     *       "scene": 1
     *     }
     * @apiParam (请求参数说明) {int} id    优惠券ID
     * @apiParam (请求参数说明) {String} name    优惠券名称
     * @apiParam (请求参数说明) {int} price   优惠券金额：单位元
     * @apiParam (请求参数说明) {String} time_begin  有效开始时间
     * @apiParam (请求参数说明) {String} time_end   有效结束时间
     * @apiParam (请求参数说明) {String} count   优惠券数量
     * @apiParam (请求参数说明) {int} scene   应用场景（暂定）：1 | 新用户优惠券
     *
     * @apiSuccessExample {json} 返回样例:
     *{"msg":"ok","errorCode":0}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     */
    public function update()
    {
        $params = $this->request->param();
        $ticket = TicketT::update($params);
        if (!$ticket) {
            throw new UpdateException();
        }
        return json(new SuccessMessage());

    }

    /**
     * @api {POST} /api/v1/ticket/handel  Android管理端/CMS管理端-优惠券状态操作
     * @apiGroup  COMMON
     * @apiVersion 1.0.1
     * @apiDescription  Android管理端/CMS管理端-优惠券状态操作
     * @apiExample {POST}  请求样例:
     * {
     * "id": 1,
     * "state": 2,
     * }
     * @apiParam (请求参数说明) {int} id 优惠券id
     * @apiParam (请求参数说明) {int} state 状态 1 | 启用；2 | 停用；3 | 删除
     * @apiSuccessExample {json} 返回样例:
     * {"msg": "ok","errorCode": 0}
     * @apiSuccess (返回参数说明) {int} errorCode 错误代码 0 表示没有错误
     * @apiSuccess (返回参数说明) {String} msg 操作结果描述
     *
     */
    public function handel()
    {
        $params = $this->request->param();
        $id = TicketT::update(['state' => CommonEnum::STATE_IS_FAIL], ['id' => $params['id']]);
        if (!$id) {
            throw new UpdateException();
        }
        return json(new SuccessMessage());

    }

    /**
     * @api {POST} /api/v1/ticket/send  管理员给指定用户发送优惠券
     * @apiGroup  CMS
     * @apiVersion 1.0.1
     * @apiDescription  管理员给指定用户发送优惠券
     * @apiExample {POST}  请求样例:
     * {
     * "u_id": 1,2,
     * "t_id": 1,
     * }
     * @apiParam (请求参数说明) {String} u_id 用户id，多个用户用逗号分隔
     * @apiParam (请求参数说明) {int} t_id 优惠券ID
     * @apiSuccessExample {json} 返回样例:
     * {"msg": "ok","errorCode": 0}
     * @apiSuccess (返回参数说明) {int} errorCode 错误代码 0 表示没有错误
     * @apiSuccess (返回参数说明) {String} msg 操作结果描述
     *
     */
    public function send()
    {
        $params = $this->request->param();

        (new TicketService())->sendTicket($params['u_id'], $params['t_id']);
        return json(new SuccessMessage());

    }

    /**
     * @api {GET} /api/v1/tickets/manage Android管理端/CMS管理端-获取优惠券列表
     * @apiGroup  COMMON
     * @apiVersion 1.0.1
     * @apiDescription
     * @apiExample {get}  请求样例:
     * https://tonglingok.com/api/v1/tickets/manage?key=2&time_begin=2019-06-28&time_end=2019-06-29&page=1&size=10
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {String} key 关键字查询： CMS端传入该字段，Android端无需传入
     * @apiParam (请求参数说明) {String} time_begin 查询开始时间：CMS端传入该字段，Android端无需传入
     * @apiParam (请求参数说明) {String} time_end 查询开始时间：CMS端传入该字段，Android端无需传入
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":1,"per_page":10,"current_page":1,"last_page":1,"data":[{"id":2,"name":"新用户优惠券2","price":5,"time_begin":"2019-10-02 00:00:00","time_end":"2019-10-09 00:00:00","count":100,"create_time":"2019-06-28 22:06:56","update_time":"2019-06-28 22:06:56","state":1,"username":"朱明良"}]}}
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} id 卡券id
     * @apiSuccess (返回参数说明) {String} name 卡券名称
     * @apiSuccess (返回参数说明) {int} price 卡券面值
     * @apiSuccess (返回参数说明) {int} count 数量
     * @apiSuccess (返回参数说明) {String} time_begin 有效期开始时间
     * @apiSuccess (返回参数说明) {String} time_end 有效期结束时间
     * @apiSuccess (返回参数说明) {int} state 状态：1 | 正常；2 | 停用
     * @apiSuccess (返回参数说明) {String} create_time 创建时间
     * @apiSuccess (返回参数说明) {String} username 创建人
     */
    public function ManageTickets($page = 1, $size = 10, $time_begin = '', $time_end = '', $key = '')
    {
        $ticks = (new TicketService())->ticketsForCMS($page, $size, $time_begin, $time_end, $key);
        return json(new SuccessMessageWithData(['data' => $ticks]));

    }

    /**
     * @api {GET} /api/v1/tickets/user 小程序获取客户拥有可使用卡券
     * @apiGroup  MINI
     * @apiVersion 1.0.1
     * @apiDescription  小程序获取客户拥有可使用卡券
     *
     * @apiExample {get}  请求样例:
     * https://tonglingok.com/api/v1/tickets/user
     * @apiSuccessExample {json} 返回样例:
     * [{"id":1,"name":"新用户优惠券","money":5,"time_begin":"2019-06-04 11:06:30","time_end":"2020-06-04 11:06:30"}]
     * @apiSuccess (返回参数说明) {int} id 卡券id
     * @apiSuccess (返回参数说明) {String} name 卡券名称
     * @apiSuccess (返回参数说明) {int} money 卡券面值
     * @apiSuccess (返回参数说明) {String} time_begin 有效期开始时间
     * @apiSuccess (返回参数说明) {String} time_end 有效期结束时间
     */
    public function userTickets()
    {
        $ticks = (new TicketService())->userTickets();
        return json(new SuccessMessageWithData(['data' => $ticks]));
    }

}