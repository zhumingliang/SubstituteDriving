<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\lib\exception\SuccessMessage;
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

}