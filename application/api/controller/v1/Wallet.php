<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\WalletService;
use app\lib\exception\SuccessMessage;
use app\lib\exception\SuccessMessageWithData;

class Wallet extends BaseController
{
    /**
     * @api {POST} /api/v1/recharge/save  Android管理端-司机充值
     * @apiGroup  Android
     * @apiVersion 1.0.1
     * @apiDescription  Android管理端-司机充值
     * @apiExample {post}  请求样例:
     *    {
     *       "d_id":1,
     *       "money":100
     *     }
     * @apiParam (请求参数说明) {int} d_id  司机ID
     * @apiParam (请求参数说明) {Float} money  充值金额
     *
     * @apiSuccessExample {json} 返回样例:
     *{"msg":"ok","errorCode":0}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     */
    public function saveRecharge()
    {
        $params = $this->request->param();
        (new WalletService())->recharge($params);
        return json(new SuccessMessage());

    }

    /**
     * @api {GET} /api/v1/recharges CMS管理端-获取充值列表
     * @apiGroup  CMS
     * @apiVersion 1.0.1
     * @apiDescription
     * @apiExample {get}  请求样例:
     * https://tonglingok.com/api/v1/recharges?page=1&size=10&type=1
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":1,"per_page":10,"current_page":1,"last_page":1,"data":[{"id":1,"money":100,"d_id":1,"create_time":"2019-07-04 18:39:40","driver":{"id":1,"username":"朱明良"}}]}}
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} id 充值id
     * @apiSuccess (返回参数说明) {Float} money 充值金额
     * @apiSuccess (返回参数说明) {String} create_time 创建时间
     * @apiSuccess (返回参数说明) {Obj} driver 被充值司机信息
     * @apiSuccess (返回参数说明) {String} driver|username 司机用户名
     */
    public function recharges($page = 1, $size = 10)
    {
        $list = (new WalletService())->recharges($page, $size);
        return json(new SuccessMessageWithData(['data' => $list]));

    }

    /**
     * @api {GET} /api/v1/recharges CMS管理端-获取指定司机充值列表
     * @apiGroup  CMS
     * @apiVersion 1.0.1
     * @apiDescription
     * @apiExample {get}  请求样例:
     * https://tonglingok.com/api/v1/recharges?page=1&size=10&type=1
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiSuccessExample {json} 返回样例:
    {"msg":"ok","errorCode":0,"code":200,"data":{"total":1,"per_page":10,"current_page":1,"last_page":1,"data":[{"id":1,"money":100,"create_time":"2019-07-04 18:39:40"}]}}
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} id 充值id
     * @apiSuccess (返回参数说明) {Float} money 充值金额
     * @apiSuccess (返回参数说明) {String} create_time 创建时间
     */
    public function driverRecharges($page = 1, $size = 10)
    {
        $d_id = $this->request->param('d_id');
        $list = (new WalletService())->driverRecharges($page, $size,$d_id);
        return json(new SuccessMessageWithData(['data' => $list]));
    }


}