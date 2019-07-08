<?php
/**
 * Created by PhpStorm.
 * User: zhumingliang
 * Date: 2018/3/21
 * Time: 下午4:11
 */

namespace app\api\service;


use app\lib\exception\TokenException;
use app\lib\exception\UpdateException;
use app\lib\exception\UserInfoException;
use app\lib\exception\WeChatException;
use think\facade\Cache;
use think\facade\Request;
use app\api\model\UserT as UserModel;
use think\facade\Session;

class UserInfo
{
    protected $iv;
    protected $encryptedData;
    protected $wxAppID;
    protected $user_id;

    function __construct($iv, $encryptedData)
    {
        $this->iv = $iv;
        $this->encryptedData = $encryptedData;
        $this->wxAppID = config('wx.app_id');
        $this->user_id = 1;//Token::getCurrentUid();
    }


    /**
     * 保存用户信息
     * @return array
     * @throws TokenException
     * @throws UserInfoException
     * @throws WeChatException
     * @throws \think\Exception
     */
    public function saveUserInfo()
    {
        $session_key = $this->getSessionKey();
        $user_info = $this->encodeUserInfo($session_key);
        $this->saveInfo($user_info);
        //更新缓存
        $this->updateCache($user_info);

    }

    /**
     * 解密微信用户信息
     * @param $session_key
     * @return mixed
     * @throws WeChatException
     */
    private function encodeUserInfo($session_key)
    {

        $pc = new WXBizDataCryptService($this->wxAppID, $session_key);
        $errCode = $pc->decryptData($this->encryptedData, $this->iv, $data);

        if ($errCode == 0) {
            return json_decode($data, true);
        } else {
            throw new WeChatException(
                [
                    'msg' => '小程序信息解码失败'
                ]);
        }
    }

    /**
     * 获取缓存的SessionKey
     * @return bool|string
     * @throws TokenException
     * @throws \think\Exception
     */
    private function getSessionKey()
    {
        $openid = Token::getCurrentOpenid();
        //$session_key = Redis::instance()->get($openid);
        $session_key = Cache::get($openid);
        if (!$session_key) {
            //$session_key过期
            throw new TokenException(
                [
                    'msg' => 'session_key过期',
                    'errorCode' => 20003]
            );

        }
        return $session_key;

    }


    private function saveInfo($user_info)
    {
        $save_res = UserModel::where('id', '=', $this->user_id)
            ->update([
                'nickName' => $user_info['nickName'],
                'avatarUrl' => $user_info['avatarUrl'],
                'gender' => $user_info['gender'],
                'province' => $user_info['province'],
                'city' => $user_info['city'],
                'country' => $user_info['country'],
                'update_time' => date("Y-m-d H:i:s", time()),
            ]);
        if (!$save_res) {
            throw new UpdateException(['msg' => '更新用户信息失败']);
        }

        return 1;


    }

    /**
     * @param $user_info
     * @return mixed
     * @throws TokenException
     * @throws \think\Exception
     */
    private function updateCache($user_info)
    {
        $cache = Token::getCurrentTokenVar();
        $cache = json_decode($cache, true);

        if (count($user_info)) {
            foreach ($user_info as $k => $v) {
                $cache[$k] = $v;
            }
        }

        $cache = json_encode($cache);
        $token = Request::header('token');
        // $result = Redis::instance()->set($token, $cache, config('setting.token_expire_in'));
        $result = Cache::set($token, $cache, config('setting.token_expire_in'));
        if (!$result) {
            throw new TokenException(['msg' => '数据缓存失败',
                'errorCode' => 20002]);
        }
        return 1;
    }

    public function bindPhone($params)
    {
        $current_code = Session::get('register_code', 'register');
        if ($current_code != $params['phone'] . '-' . $params['code']) {
            throw new UpdateException(['errorCode' => '10002', 'msg' => '验证码不正确']);
        }
        $res = UserModel::update(['phone' => $params['phone']], ['id' => 1]);
        if (!$res) {
            throw new UpdateException(['msg' => '绑定手机用户手机号失败']);
        }

        $this->updateCache(['phone' => $params['phone']]);

    }

}