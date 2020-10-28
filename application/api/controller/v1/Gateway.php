<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\model\SocketBindT;
use app\api\service\GatewayService;
use app\api\service\WalletService;
use app\lib\exception\SuccessMessage;
use app\lib\exception\SuccessMessageWithData;

class Gateway extends BaseController
{
    /**
     * @api {POST} /api/v1/gateway/bind  Android司机端/小程序端-绑定账号与websocket通讯关系
     * @apiGroup   COMMON
     * @apiVersion 1.0.1
     * @apiDescription  Android司机端/小程序端-绑定账号与websocket通讯关系
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
        $grade = \app\api\service\Token::getCurrentTokenVar('type');
        $company_id = \app\api\service\Token::getCurrentTokenVar('company_id');

        $group = 'company-' . $company_id;
        \GatewayClient\Gateway::joinGroup($client_id, $group);
        \GatewayClient\Gateway::bindUid($client_id, $grade . '-' . $u_id);
        //检测余额
        (new WalletService())->checkDriverBalance($u_id);
        return json(new SuccessMessage());

    }

    /**
     * @api {GET} /api/v1/gateway/checkOnline Android司机端-检测司机websocket服务是否在线
     * @apiGroup  Android
     * @apiVersion 1.0.1
     * @apiDescription   Android司机端-检测司机websocket服务是否在线
     * @apiExample {get}  请求样例:
     * https://tonglingok.com/api/v1/gateway/checkOnline
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"online":1}]}
     * @apiSuccess (返回参数说明) {int} online 1|在线；0|离线
     */
    public function checkOnline()
    {
        $u_id = \app\api\service\Token::getCurrentUid();
        $online = GatewayService::isDriverUidOnline($u_id);
        return json(new SuccessMessageWithData(['data' => ['online' => $online]]));

    }

    public function onlineClients()
    {
        $list = \GatewayClient\Gateway::getAllUidList();
        return json(new SuccessMessageWithData(['data' => $list]));
    }

}