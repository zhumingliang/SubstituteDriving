<?php
/**
 * Created by PhpStorm.
 * User: mingliang
 * Date: 2018/11/30
 * Time: 12:56 AM
 */

namespace app\api\service;

use think\Exception;
use zml\tp_tools\Curl;

class AccessToken
{
    private $tokenUrl;
    const TOKEN_CACHED_KEY = 'access_token';
    const TOKEN_EXPIRE_IN = 7000;

    function __construct()
    {
        $url = config('wx.access_token_url');
        $url = sprintf($url, config('wx.app_id'), config('wx.app_secret'));
        $this->tokenUrl = $url;
    }

    // 建议用户规模小时每次直接去微信服务器取最新的token
    // 但微信access_token接口获取是有限制的 2000次/天
    public function get()
    {
        $cache_token = $this->getFromCache();
        $token = $cache_token['access_token'];
        if (!$token) {
            return $this->getFromWxServer();
        } else {
            return $token;
        }
    }

    private function getFromCache()
    {
        $token = cache(self::TOKEN_CACHED_KEY);
        if ($token) {
            return $token;
        }
        return null;
    }


    /**
     * @return mixed
     * @throws Exception
     */
    private function getFromWxServer()
    {

        $token = Curl::get($this->tokenUrl);
        $token = json_decode($token, true);
        if (!$token) {
            throw new Exception('获取AccessToken异常');
        }
        if (!empty($token['errcode'])) {
            throw new Exception($token['errmsg']);
        }
        $this->saveToCache($token);
        return $token['access_token'];
    }

    private function saveToCache($token)
    {
        cache(self::TOKEN_CACHED_KEY, $token, self::TOKEN_EXPIRE_IN);
    }
}