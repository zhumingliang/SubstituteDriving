<?php


namespace app\api\controller\v1;


use app\api\service\CompanyService;
use app\lib\exception\SuccessMessage;
use think\facade\Request;

class Company
{
    /**
     * @api {POST} /api/v1/agent/save CMS管理端-新增代理商
     * @apiGroup   CMS
     * @apiVersion 1.0.1
     * @apiDescription   CMS管理端-新增代理商
     * @apiExample {post}  请求样例:
     *    {
     *       "company": "马鞍山鹏凯代驾",
     *       "username": "韩先生",
     *       "phone": "18956225230",
     *       "province": "安徽省",
     *       "city": "马鞍山市"
     *     }
     * @apiParam (请求参数说明) {String} company  企业名称
     * @apiParam (请求参数说明) {String} username  联系人
     * @apiParam (请求参数说明) {String} phone  手机号
     * @apiParam (请求参数说明) {String} province  省
     * @apiParam (请求参数说明) {String} city  市
     * @apiSuccessExample {json} 返回样例:
     *{"msg":"ok","errorCode":0}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     */
    public function save()
    {
        $params = Request::param();
        (new CompanyService())->save($params);
        return json(new SuccessMessage());
    }

}