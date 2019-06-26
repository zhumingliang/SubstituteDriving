<?php
/**
 * Created by PhpStorm.
 * User: mingliang
 * Date: 2018/9/18
 * Time: 上午9:40
 */

namespace app\api\controller\v1;

use app\api\model\ImgT;
use app\api\model\UserT;
use app\api\model\UserV;
use app\api\service\ImageService;
use app\api\service\UserService;
use app\api\validate\UserInfo;

use app\api\controller\BaseController;
use  app\api\service\UserInfo as UserInfoService;
use app\lib\exception\SuccessMessage;
use \app\api\service\Token as TokenService;
use app\lib\exception\UpdateException;
use app\lib\exception\UserInfoException;

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
     * @apiSuccess (返回参数说明) {int} error_code 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     *
     * @param $iv
     * @param $encryptedData
     * @return \think\response\Json
     * @throws ParameterException
     * @throws \app\lib\exception\TokenException
     * @throws \app\lib\exception\WeChatException
     * @throws \think\Exception
     */
    public function userInfo($iv, $encryptedData)
    {
        $user_info = new UserInfoService($iv, $encryptedData);
        $user_info->saveUserInfo();
        return json(new SuccessMessage());
    }






}