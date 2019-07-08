<?php
/**
 * Created by PhpStorm.
 * User: mingliang
 * Date: 2018/9/18
 * Time: 上午9:40
 */

namespace app\api\controller\v1;

use app\api\model\UserT;
use app\api\controller\BaseController;
use  app\api\service\UserInfo as UserInfoService;
use app\lib\exception\SuccessMessage;
use think\facade\Cache;

class User extends BaseController
{
    /**
     * @api {POST} /api/v1/user/info 小程序用户信息获取并解密和存储
     * @apiGroup  MINI
     * @apiVersion 1.0.1
     * @apiDescription  后台用户登录
     * @apiExample {post}  请求样例:
     *    {
     *       "iv": "wx4f4bc4dec97d474b",
     *       "encryptedData": "CiyLU1Aw2Kjvrj"
     *     }
     * @apiParam (请求参数说明) {String} iv    加密算法的初始向量
     * @apiParam (请求参数说明) {String} encryptedData   包括敏感数据在内的完整用户信息的加密数据
     *
     * @apiSuccessExample {json} 返回样例:
     *{"msg":"ok","errorCode":0}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     *
     */
    public function userInfo()
    {
        $params = $this->request->param();
        $iv = $params['iv'];
        $encryptedData = $params['encryptedData'];
        $user_info = new UserInfoService($iv, $encryptedData);
        $user_info->saveUserInfo();
        return json(new SuccessMessage());
    }

    /**
     * @api {POST} /api/v1/user/bindPhone 小程序客户端-绑定手机号
     * @apiGroup  MINI
     * @apiVersion 1.0.1
     * @apiDescription  小程序客户端-绑定手机号
     * @apiExample {post}  请求样例:
     *    {
     *       "phone": "18956225230",
     *       "code": "34982"
     *     }
     * @apiParam (请求参数说明) {String} phone  用户输入手机号
     * @apiParam (请求参数说明) {String} code   用户输入验证码
     *
     * @apiSuccessExample {json} 返回样例:
     *{"msg":"ok","errorCode":0}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     *
     */
    public function bindPhone()
    {
        $params = $this->request->param();
        (new UserInfoService('', ''))->bindPhone($params);
        return json(new SuccessMessage());
    }


    /**
     * @api {GET} /api/v1/user/login/out  小程序客户端-注销登录
     * @apiGroup  MINI
     * @apiVersion 1.0.1
     * @apiDescription 小程序客户端-注销登录
     * @apiExample {get}  请求样例:
     * https://tonglingok.com/api/v1/user/login/out
     * @apiSuccessExample {json} 返回样例:
     *{"msg":"ok","errorCode":0}
     * @apiSuccess (返回参数说明) {int} error_code 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     *
     */
    public function loginOut()
    {
        //清除用户手机号
        UserT::update(['phone' => ''], ['id' => \app\api\service\Token::getCurrentUid()]);
        $token = think\facade\Request::header('token');
        Cache::rm($token);
        return json(new SuccessMessage());
    }


}