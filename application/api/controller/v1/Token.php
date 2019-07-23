<?php
/**
 * Created by PhpStorm.
 * User: mingliang
 * Date: 2018/5/27
 * Time: 上午9:53
 */

namespace app\api\controller\v1;


use app\api\model\FormidT;
use app\api\model\TestT;
use app\api\model\UserT;
use app\api\service\AdminToken;
use app\api\service\DriverToken;
use app\api\service\UserToken;
use app\api\validate\TokenGet;
use app\lib\enum\CommonEnum;
use app\lib\exception\SuccessMessage;
use app\lib\exception\SuccessMessageWithData;
use app\lib\exception\TokenException;
use think\Controller;
use think\facade\Cache;
use think\facade\Request;

class Token extends Controller
{
    /**
     * @api {POST} /api/v1/token/admin  CMS获取登陆token
     * @apiGroup  CMS
     * @apiVersion 1.0.1
     * @apiDescription  后台用户登录
     * @apiExample {post}  请求样例:
     *    {
     *       "account": "18956225230",
     *       "pwd": "a123456"
     *     }
     * @apiParam (请求参数说明) {String} phone    用户手机号
     * @apiParam (请求参数说明) {String} pwd   用户密码
     * @apiSuccessExample {json} 返回样例:
     *{"msg":"ok","errorCode":0,"data":{{"token":"b9c1b6b884c2fc6c53048972eaf785a7","grade":1}}
     * @apiSuccess (返回参数说明) {String} grade 用户等级:1 | 管理员；2 | 保险公司
     * @apiSuccess (返回参数说明) {String} token 口令令牌，每次请求接口需要传入，有效期 2 hours
     */
    public function getAdminToken()
    {
        $params = $this->request->param();
        $at = new AdminToken($params['account'], $params['pwd']);
        $token = $at->get();
        return json(new SuccessMessageWithData(['data' => $token]));
    }

    /**
     * @api {POST} /api/v1/token/android  Android客户端获取登陆token
     * @apiGroup  Android
     * @apiVersion 1.0.1
     * @apiDescription  Android客户端获取登陆token
     * @apiExample {post}  请求样例:
     *    {
     *       "account": "18956225230",
     *       "pwd": "a123456"
     *       "code": "121212",
     *       "type": "driver",
     *     }
     * @apiParam (请求参数说明) {String} phone    用户手机号
     * @apiParam (请求参数说明) {String} pwd   用户密码
     * @apiParam (请求参数说明) {String} code   安卓手机唯一识别码
     * @apiParam (请求参数说明) {String} type  当前登录用户类别：driver|司机；manager|管理员
     * @apiSuccessExample {json} 返回样例:
      *{"msg":"ok","errorCode":0,"data":{{"username":"朱明良","online":1,"token":"d3fd8533d1a7c032e08e77e8c8fb3bb9"}}
     * @apiSuccess (返回参数说明) {String} username 用户名
     * @apiSuccess (返回参数说明) {int} online 司机在线状态：1 | 在线；2 | 下线
     * @apiSuccess (返回参数说明) {String} token 口令令牌，每次请求接口需要传入，有效期 24 hours
     */
    public function getAndroidToken()
    {
        $params = $this->request->param();
        $at = new DriverToken($params['account'], $params['pwd'], $params['code'], $params['type']);
        $token = $at->get();
        return json(new SuccessMessageWithData(['data' => $token]));
    }

    /**
     * @api {GET} /api/v1/token/login/out  CMS退出登陆
     * @apiGroup  CMS
     * @apiVersion 1.0.1
     * @apiDescription CMS退出当前账号登陆。
     * @apiExample {get}  请求样例:
     * https://tonglingok.com/api/v1/token/loginOut
     * @apiSuccessExample {json} 返回样例:
     *{"msg":"ok","errorCode":0}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     *
     */
    public function loginOut()
    {

        $token = Request::header('token');
        Cache::rm($token);
        return json(new SuccessMessage());
    }

    /**
     * @api {GET} /api/v1/token/user  小程序端获取登录token
     * @apiGroup  MINI
     * @apiVersion 1.0.1
     * @apiDescription  微信用户登录获取token
     * @apiExample {get}  请求样例:
     * https://tonglingok.com/api/v1/token/user?code=mdksk
     * @apiParam (请求参数说明) {String} code    小程序code
     * @apiSuccessExample {json} 返回样例:
     *{"msg":"ok","errorCode":0,"data":{"token":"f4ad56e55cad93833180186f22586a08","type":1,"phone":"18956225230"}}
     * @apiSuccess (返回参数说明) {Sting} token 口令令牌，每次请求接口需要传入，有效期 2 hours
     * @apiSuccess (返回参数说明) {Sting} phone 手机号
     * @apiSuccess (返回参数说明) {int} type 数据库是否存储小程序用户信息:1 | 已存储；2 | 未存储,需要请求userInfo接口
     */
    public function getUserToken()
    {
        $code = $this->request->param('code');
        $ut = new UserToken($code);
        $token = $ut->get();
        return json(new SuccessMessageWithData(['data' => $token]));

    }


}