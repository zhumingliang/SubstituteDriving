<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\LogService;
use app\api\service\SendSMSService;
use app\lib\exception\SuccessMessage;
use app\lib\exception\SuccessMessageWithData;
use think\Cache;
use think\facade\Request;
use think\facade\Session;

class SendSMS extends BaseController
{
    /**
     * @api {POST} /api/v1/sms/register  MINI小程序端-发送验证手机验证码
     * @apiGroup  MINI
     * @apiVersion 1.0.1
     * @apiDescription  MINI小程序端-发送验证手机验证码
     * @apiExample {post}  请求样例:
     *    {
     *       "phone":"18956225230"
     *     }
     * @apiParam (请求参数说明) {String} phone  手机号
     * @apiSuccessExample {json} 返回样例:
     *{"msg":"ok","errorCode":0}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     */
    public function sendCodeToMINI()
    {
        $params = $this->request->param();
        (new SendSMSService())->sendCode($params['phone'], 'register');
        return json(new SuccessMessage());

    }

    /**
     * @api {POST} /api/v1/sms/login  Android司机端-登录时发送手机验证码
     * @apiGroup  Android
     * @apiVersion 1.0.1
     * @apiDescription  Android司机端-登录时发送手机验证码
     * @apiExample {post}  请求样例:
     *    {
     *       "phone":"18956225230"
     *     }
     * @apiParam (请求参数说明) {String} phone  手机号
     * @apiSuccessExample {json} 返回样例:
     *{"msg":"ok","errorCode":0}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     */
    public function sendCodeToAndroid()
    {
        $params = $this->request->param();
        (new SendSMSService())->sendCode($params['phone'], 'login');
        return json(new SuccessMessage());
    }

    /**
     * 处理没有成功发送短信
     */
    public function sendHandel()
    {
        (new SendSMSService())->sendHandel();

    }

    /**
     * 发送短信给司机
     */
    public function sendOderToDriver()
    {
        $phone = Request::param('phone');
        $order_num = Request::param('order_num');
        $create_time = Request::param('create_time');
        (new SendSMSService())->sendOrderSMS($phone, ['code' => 'OK' . $order_num,
            'order_time' => $create_time]);
    }

    public function records($sign = 0, $phone = '', $state = 3)
    {
        $params = Request::param();
        $params['sign'] = $sign;
        $params['phone'] = $phone;
        $params['state'] = $state;
        $records = (new SendSMSService())->records($params);
        return json(new  SuccessMessageWithData(['data' => $records]));
    }

}