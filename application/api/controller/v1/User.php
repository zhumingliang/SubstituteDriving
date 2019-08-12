<?php
/**
 * Created by PhpStorm.
 * User: mingliang
 * Date: 2018/9/18
 * Time: 上午9:40
 */

namespace app\api\controller\v1;

use app\api\model\UserPublicT;
use app\api\model\UserT;
use app\api\controller\BaseController;
use  app\api\service\UserInfo as UserInfoService;
use app\api\service\UserInfo;
use app\api\service\UserService;
use app\lib\exception\SuccessMessage;
use app\lib\exception\SuccessMessageWithData;
use app\lib\exception\UpdateException;
use think\facade\Cache;
use think\facade\Request;

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
     * @api {POST} /api/v1/user/public/info 保存微信公众号用户信息
     * @apiGroup  PUBLIC
     * @apiVersion 1.0.1
     * @apiDescription  后台用户登录
     * @apiExample {post}  请求样例:
     *    {
     *       "openid": "wx4f4bc4dec97d474b",
     *       "nickname": "",
     *       "sex": "",
     *       "city": "",
     *       "county": "",
     *       "province": "",
     *       "language": "",
     *       "headimgurl": "",
     *     }
     * @apiParam (请求参数说明) {String} openid    加密算法的初始向量
     * @apiParam (请求参数说明) {String} nickname   昵称
     * @apiParam (请求参数说明) {String} sex   性别
     * @apiParam (请求参数说明) {String} city  市
     * @apiParam (请求参数说明) {String} county  国家
     * @apiParam (请求参数说明) {String} province  省
     * @apiParam (请求参数说明) {String} headimgurl   头像
     * @apiSuccessExample {json} 返回样例:
     *{"msg":"ok","errorCode":0}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     *
     */
    public function userPublicInfo()
    {
        $params = $this->request->param();
        $u_id = \app\api\service\Token::getCurrentUid();
        $user = UserPublicT::update($params, ['id' => $u_id]);
        if (!$user) {
            throw new UpdateException();
        }
        return json(new SuccessMessage());
    }

    /**
     * @api {POST} /api/v1/user/bindPhone -绑定手机号小程序客户端/公众号客户端
     * @apiGroup  COMMON
     * @apiVersion 1.0.1
     * @apiDescription  小程序客户端/公众号客户端
     * @apiExample {post}  请求样例:
     *    {
     *       "phone": "18956225230",
     *       "code": "34982"
     *       "scene": 1
     *     }
     * @apiParam (请求参数说明) {String} phone  用户输入手机号
     * @apiParam (请求参数说明) {String} code   用户输入验证码
     * @apiParam (请求参数说明) {int} scene   验证类别：1|小程序验证；2|公众号验证
     *
     * @apiSuccessExample {json} 返回样例:
     *{"msg":"ok","errorCode":0,"data"{"ticket:1","name":"","time_begin":"","time_end":"","money":""}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {int} ticket 是否有优惠券发放：1|有；2|没有
     * @apiSuccess (返回参数说明) {string} name 优惠券名称
     * @apiSuccess (返回参数说明) {string} time_begin 优惠券有效期开始时间
     * @apiSuccess (返回参数说明) {string} time_end 优惠券有效期结束时间
     * @apiSuccess (返回参数说明) {string} money 优惠券金额
     *
     */
    public function bindPhone()
    {
        $params = $this->request->param();
        $ticket = (new UserInfoService('', ''))->bindPhone($params);
        return json(new SuccessMessageWithData(['data' => $ticket]));
    }


    /**
     * @api {POST} /api/v1/user/check/bind 公众号客户端检测用户是否绑定手机号
     * @apiGroup  PUBLIC
     * @apiVersion 1.0.1
     * @apiDescription  公众号客户端检测用户是否绑定手机号
     * @apiSuccessExample {json} 返回样例:
     *{"msg":"ok","errorCode":0,"data"{"ticket:1","name":"","time_begin":"","time_end":"","money":""}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {int} bind 否绑定手机号：1|有；2|没有
     *
     */
    public function checkBind()
    {
        $u_id = \app\api\service\Token::getCurrentUid();
        $user = UserPublicT::get($u_id);
        if (empty($user->phone)) {
            $res = 2;
        } else {
            $res = 1;
        }
        return json(new SuccessMessageWithData(['data' => ['bind' => $res]]));

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
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     *
     */
    public function loginOut()
    {
        //清除用户手机号
        UserT::update(['phone' => ''], ['id' => \app\api\service\Token::getCurrentUid()]);
        $token = Request::header('token');
        Cache::rm($token);
        return json(new SuccessMessage());
    }

    /**
     * @api {GET} /api/v1/users CMS管理端-用户管理获取用户列表
     * @apiGroup  CMS
     * @apiVersion 1.0.1
     * @apiDescription   CMS管理端-用户管理获取用户列表
     * @apiExample {get}  请求样例:
     * https://tonglingok.com/api/v1/users?page=1&size=10&name=''&time_begin=''&time_end=''&phone=''&money_min=1&money_max=100&count_min=0&count_max=9
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {String} name 用户名称
     * @apiParam (请求参数说明) {String} phone 用户手机号
     * @apiParam (请求参数说明) {int} time_begin 查询开始时间
     * @apiParam (请求参数说明) {int} time_end 查询结束时间
     * @apiParam (请求参数说明) {int} money_min 消费金额-金额开始
     * @apiParam (请求参数说明) {int} money_max 消费金额-金额结束
     * @apiParam (请求参数说明) {int} count_min 订单数量-开始
     * @apiParam (请求参数说明) {int} count_max 订单数量-结束
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":3,"per_page":10,"current_page":1,"last_page":1,"data":[{"id":5,"nickName":null,"phone":null,"source":"小程序用户","create_time":"2019-07-17 17:39:18","parent_name":null,"money":0,"count":0},{"id":4,"nickName":null,"phone":"18956225230","source":"小程序用户","create_time":"2019-07-17 15:59:06","parent_name":null,"money":80,"count":1},{"id":3,"nickName":null,"phone":"13415012786","source":"小程序用户","create_time":"2019-07-17 15:58:14","parent_name":null,"money":0,"count":0}]}}
     * @apiSuccess (返回参数说明) {Obj} orders 订单列表
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} id 用户id
     * @apiSuccess (返回参数说明) {String} nickName 名称
     * @apiSuccess (返回参数说明) {String} phone 乘客手机号
     * @apiSuccess (返回参数说明) {Float} money 订单金额
     * @apiSuccess (返回参数说明) {int} count 订单数量
     * @apiSuccess (返回参数说明) {String} source 下单来源
     * @apiSuccess (返回参数说明) {String} parent_name 创建人
     * @apiSuccess (返回参数说明) {String}  create_time 注册时间
     */
    public function users($page = 1, $size = 10, $name = '', $time_begin = '', $time_end = '', $phone = '', $money_min = 0, $money_max = 0, $count_min = 0, $count_max = 0)
    {
        $users = (new UserService())->users($page, $size, $name, $time_begin, $time_end, $phone, $money_min, $money_max, $count_min, $count_max);
        return json(new SuccessMessageWithData(['data' => $users]));

    }


}