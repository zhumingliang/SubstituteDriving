<?php


namespace app\api\service;


use app\api\model\AdminT;
use app\api\model\DriverT;
use app\lib\enum\CommonEnum;
use app\lib\exception\TokenException;
use think\Exception;
use think\facade\Cache;

class DriverToken extends Token
{
    protected $account;
    protected $pwd;
    protected $code;


    function __construct($account, $pwd, $code)
    {
        $this->account = $account;
        $this->pwd = $pwd;
        $this->code = $code;
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function get()
    {
        try {

            $admin = DriverT::where('account', '=', $this->account)
                ->where('state', CommonEnum::STATE_IS_OK)
                ->find();

            if (is_null($admin) || sha1($this->pwd) != $admin->pwd) {
                throw new TokenException([
                    'msg' => '账号或者密码不正确',
                    'errorCode' => 30000
                ]);
            }

            // $this->saveLog($admin->id, $admin->username);
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
        $expire_in = config('setting.token_phone_expire_in');
        $request = Cache::set($key, $value, $expire_in);


        if (!$request) {
            throw new TokenException([
                'msg' => '服务器缓存异常',
                'errorCode' => 20002
            ]);
        }

        return [
            'token' => $key,
            'username' => $cachedValue['username'],
            'online'=>$cachedValue['online']
        ];
    }

    private function prepareCachedValue($admin)
    {

        $cachedValue = [
            'u_id' => $admin->id,
            'phone' => $admin->phone,
            'username' => $admin->username,
            'account' => $admin->account,
            'phone_code' => $admin->phone_code,
            'online' => $admin->online,
        ];
        return $cachedValue;
    }

}