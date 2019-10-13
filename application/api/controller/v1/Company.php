<?php


namespace app\api\controller\v1;


use app\api\service\CompanyService;
use app\lib\exception\SuccessMessage;
use app\lib\exception\SuccessMessageWithData;
use think\facade\Request;
use think\response\Json;

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

    /**
     * @api {POST} /api/v1/agent/update CMS管理端-更新代理商信息
     * @apiGroup   CMS
     * @apiVersion 1.0.1
     * @apiDescription   CMS管理端-更新代理商信息
     * @apiExample {post}  请求样例:
     *    {
     *       "id": 1,
     *       "company": "马鞍山鹏凯代驾",
     *       "username": "韩先生",
     *       "phone": "18956225230",
     *       "province": "安徽省",
     *       "city": "马鞍山市"
     *     }
     * @apiParam (请求参数说明) {String} id  企业Id
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
    public function update()
    {
        $params = Request::param();
        (new CompanyService())->update($params);
        return json(new SuccessMessage());
    }

    /**
     * @api {GET} /api/v1/agents CMS管理端-获取加盟商列表
     * @apiGroup  CMS
     * @apiVersion 1.0.1
     * @apiDescription CMS管理端-获取加盟商列表
     * @apiExample {get}  请求样例:
     * https://tonglingok.com/api/v1/agents?page=1&size=10&phone=&company=&username=
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {String} phone 手机号
     * @apiParam (请求参数说明) {String} company 加盟商名称
     * @apiParam (请求参数说明) {String} username  用户名称
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":3,"per_page":20,"current_page":1,"last_page":1,"data":[{"id":4,"company":"马鞍山鹏凯代驾","phone":"15551716555","province":"安徽省","city":"马鞍山市","state":1,"create_time":"2019-10-14 00:30:15","username":"韩龙鹏"},{"id":2,"company":"测试","phone":"18956225230","province":"安徽省","city":"铜陵市","state":1,"create_time":"2019-10-10 01:01:12","username":null},{"id":1,"company":"铜陵奥凯代驾","phone":"13515623335","province":"安徽省","city":"铜陵市","state":1,"create_time":"2019-09-24 16:45:54","username":"张伊"}]}}
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} id 加盟商id
     * @apiSuccess (返回参数说明) {String} company 企业名称
     * @apiSuccess (返回参数说明) {String} username  联系人姓名
     * @apiSuccess (返回参数说明) {String} phone  联系人手机号
     * @apiSuccess (返回参数说明) {String} province 归属省
     * @apiSuccess (返回参数说明) {String} city 归属市
     * @apiSuccess (返回参数说明) {int} state 状态：1 | 正常；2 | 停用
     * @apiSuccess (返回参数说明) {String} create_time 创建时间
     */
    public function agents($page = 1, $size = 20, $phone = '', $company = '', $username = '')
    {
        $agents = (new CompanyService())->agents($page, $size, $phone, $company, $username);
        return json(new SuccessMessageWithData(['data' => $agents]));
    }


}