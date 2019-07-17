<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\model\MessageT;
use app\lib\exception\SaveException;
use app\lib\exception\SuccessMessage;
use app\lib\exception\SuccessMessageWithData;

class Message extends BaseController
{
    /**
     * @api {GET} /api/v1/messages  CMS管理端-获取反馈已经列表
     * @apiGroup   CMS
     * @apiVersion 1.0.1
     * @apiDescription    CMS管理端-获取反馈已经列表
     * @apiExample {get}  请求样例:
     * https://tonglingok.com/api/v1/messages?page=1&size=20
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":1,"per_page":20,"current_page":1,"last_page":1,"data":[{"id":1,"u_id":1,"remark":"很不错哦","create_time":"2019-07-17 11:29:46","update_time":"2019-07-17 11:29:46","user":{"id":1,"nickName":"linzx89757","phone":"13415012786"}}]}}
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} id 反馈ID
     * @apiSuccess (返回参数说明) {String} remark 反馈内容
     * @apiSuccess (返回参数说明) {Obj} user  反馈人信息
     * @apiSuccess (返回参数说明) {Obj} nickName  反馈人用户名称
     * @apiSuccess (返回参数说明) {String} phone  反馈人手机号
     */
    public function messages($page = 1, $siz = 20)
    {
        $messages = MessageT::messages($page, $siz);
        return json(new SuccessMessageWithData(['data' => $messages]));
    }

    /**
     * @api {POST} /api/v1/message/save 小程序端-新增反馈
     * @apiGroup  MINI
     * @apiVersion 1.0.1
     * @apiDescription 小程序端-新增反馈
     * @apiExample {post}  请求样例:
     *    {
     *       "remark":"很好用哦"
     *     }
     * @apiParam (请求参数说明) {String} remark 反馈信息
     * @apiSuccessExample {json} 返回样例:
     *{"msg":"ok","errorCode":0}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     */
    public function save()
    {
        $info = $this->request->param();
        $info['u_id'] = \app\api\service\Token::getCurrentUid();
        $res = MessageT::create($info);
        if (!$res) {
            throw  new SaveException();
        }
        return json(new SuccessMessage());

    }


}