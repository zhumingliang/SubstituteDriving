<?php

namespace app\api\service;


use app\api\model\AdminT;

use app\lib\enum\CommonEnum;
use app\lib\exception\TokenException;
use think\Exception;
use think\facade\Cache;

class AdminToken extends Token
{
    protected $account;
    protected $pwd;


    function __construct($account, $pwd)
    {
        $this->account = $account;
        $this->pwd = $pwd;
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function get()
    {
        try {

            $admin = AdminT::where('account', '=', $this->account)
                ->where('state', CommonEnum::STATE_IS_OK)
                ->find();

            if (is_null($admin) || (sha1($this->pwd) != $admin->pwd)) {
                throw new TokenException([
                    'msg' => '账号或密码不正确',
                    'errorCode' => 30000
                ]);
            }
            /**
             * 获取缓存参数
             */
            $cachedValue = $this->prepareCachedValue($admin);
            /**
             * 缓存数据
             */
            $token = $this->saveToCache('', $cachedValue);
            return $token;

        } catch (Exception $e) {
            throw $e;
        }

    }


    /**
     * @param $key
     * @param $cachedValue
     * @return mixed
     * @throws TokenException
     */
    private function saveToCache($key, $cachedValue)
    {
        $key = empty($key) ? self::generateToken() : $key;
        $value = json_encode($cachedValue);
        $expire_in = config('setting.token_cms_expire_in');
        $request = Cache::remember($key, $value, $expire_in);


        if (!$request) {
            throw new TokenException([
                'msg' => '服务器缓存异常',
                'errorCode' => 20002
            ]);
        }

        return [
            'token' => $key,
            'grade' => $cachedValue['grade']
        ];
    }

    private function prepareCachedValue($admin)
    {

        $cachedValue = [
            'u_id' => $admin->id,
            'phone' => $admin->phone,
            'username' => $admin->username,
            'account' => $admin->account,
            'grade' => $admin->grade,
            'type' => $admin->grade == 1 ? 'manager' : 'insurance'
        ];
        return $cachedValue;
    }


}