<?php


namespace app\api\service;


use app\lib\enum\ScopeEnum;
use app\lib\exception\ForbiddenException;
use app\lib\exception\TokenException;
use think\Cache;
use think\Exception;
use think\facade\Request;
use zml\tp_tools\Redis;

class Token
{
    public static function generateToken()
    {
        //32个字符组成一组随机字符串
        $randChars = getRandChar(32);
        //用三组字符串，进行md5加密
        $timestamp = $_SERVER['REQUEST_TIME_FLOAT'];
        //salt 盐
        $salt = config('secure.token_salt');

        return md5($randChars . $timestamp . $salt);
    }

    /**
     * @param string $key
     * @return mixed
     * @throws Exception
     * @throws TokenException
     */
    public static function getCurrentTokenVar($key = '')
    {

        $token = Request::header('token');
        //$vars = Redis::instance()->get($token);
        $vars = \think\facade\Cache::get($token);
        if (!$vars) {
            throw new TokenException();
        } else {
            if ($key == '') {
                return $vars;
            }
            if (!is_array($vars)) {
                $vars = json_decode($vars, true);
            }
            if (array_key_exists($key, $vars)) {
                return $vars[$key];
            } else {
                throw new Exception('尝试获取的Token变量并不存在');
            }
        }
    }


    /**
     * @param string $key
     * @param $token
     * @return mixed
     * @throws Exception
     * @throws TokenException
     */
    public static function getCurrentTokenVarWithToken($key = '', $token)
    {

        $vars = \think\facade\Cache::get($token);
        if (!$vars) {
            throw new TokenException();
        } else {
            if ($key == '') {
                return $vars;
            }
            if (!is_array($vars)) {
                $vars = json_decode($vars, true);
            }
            if (array_key_exists($key, $vars)) {
                return $vars[$key];
            } else {
                throw new Exception('尝试获取的Token变量并不存在');
            }
        }
    }


    /**
     * @return mixed
     * @throws Exception
     * @throws TokenException
     */
    public static function getCurrentUid()
    {
        //token
        $uid = self::getCurrentTokenVar('u_id');
        return $uid;
    }


    /**
     * @return mixed
     * @throws Exception
     * @throws TokenException
     */
    public static function getCurrentOpenid()
    {
        $uid = self::getCurrentTokenVar('openId');
        return $uid;
    }


    public static function verifyToken($token)
    {
        $exist = Cache::get($token);
        if ($exist) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $checkedOpenid
     * @return bool
     * @throws Exception
     * @throws TokenException
     */
    public static function isValidOperate($checkedOpenid)
    {
        if (!$checkedOpenid) {
            throw new Exception('检查openid时必须传入一个被检查的openid');
        }
        $currentOperateUID = self::getCurrentOpenid();
        if ($currentOperateUID == $checkedOpenid) {
            return true;
        }
        return false;
    }

}