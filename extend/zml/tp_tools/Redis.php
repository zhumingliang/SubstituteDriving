<?php
/**
 * Created by PhpStorm.
 * User: zhumingliang
 * Date: 2018/1/29
 * Time: 下午11:45
 */

namespace zml\tp_tools;

use Rollbar\Payload\Level;
use Rollbar\Rollbar;

/**
 * Class Redis redis操作类，集成了redis常用的操作方法
 * Power by zml
 * @time,2018/1/30   j
 */
class Redis
{
    public $redisObj = null;//redis实例化时静态变量

    static protected $instance;
    protected $sn;
    protected $index = 0;
    protected $port = 6379;
    protected $auth = "waHqes-nijpi8-ruwqex";
    protected $host = "121.37.255.12";

    public function __construct($options = [])
    {
        /* if (!config('app.app_debug')) {
             $this->host = '10.66.217.183';
             $this->auth='crs-qwfosn9e:mengant@2018';
         }*/
        $host = trim(isset($options["host"]) ? $options["host"] : $this->host);
        $port = trim(isset($options["port"]) ? $options["port"] : $this->port);
        $auth = trim(isset($options["auth"]) ? $options["auth"] : $this->auth);
        $index = trim(isset($options["index"]) ? $options["index"] : $this->index);
        if (!is_integer($index) && $index > 16) {
            $index = 0;
        }
        $sn = md5("{$host}{$port}{$auth}{$index}");
        $this->sn = $sn;
        if (!isset($this->redisObj[$this->sn])) {
            try {
                $this->redisObj[$this->sn] = new \Redis();
                $this->redisObj[$this->sn]->connect($host, $port);
                $this->redisObj[$this->sn]->auth($auth);
                $this->redisObj[$this->sn]->select($index);
            } catch (\Exception $e) {
                // Rollbar::log(Level::ERROR, $e->getMessage());
                try {
                    $this->redisObj[$this->sn] = new \Redis();
                    $this->redisObj[$this->sn]->connect($host, $port);
                    $this->redisObj[$this->sn]->auth($auth);
                    $this->redisObj[$this->sn]->select($index);
                } catch (\Exception $e) {
                    //   Rollbar::log(Level::ERROR, $e->getMessage());
                }
            }
        }
        $this->redisObj[$this->sn]->sn = $sn;
        $this->index = $index;
        return;
    }

    /**
     * Power: zml
     * @param array $options
     * @return Redis
     */
    public static function instance($options = [])
    {
        return new Redis($options);
    }

    public function getKeys($key = '*')
    {
        return $this->redisObj[$this->sn]->getKeys($key);
    }


    public function setExpire($key, $time = 0)
    {

        if (!$key) {
            return false;
        }
        switch (true) {
            case ($time == 0):
                return $this->redisObj[$this->sn]->expire($key, 0);
                break;
            case ($time > time()):
                $this->redisObj[$this->sn]->expireAt($key, $time);
                break;
            default:
                return $this->redisObj[$this->sn]->expire($key, $time);
        }
    }


    /*------------------------------------start 1.string结构----------------------------------------------------*/
    /**
     * 增，设置值  构建一个字符串
     * @param string $key KEY名称
     * @param string $value 设置值
     * @param int $timeOut 时间  0表示无过期时间
     * @return true【总是返回true】
     */
    public function set($key, $value, $timeOut = 0)
    {
        $setRes = $this->redisObj[$this->sn]->set($key, $value);
        if ($timeOut > 0) $this->redisObj[$this->sn]->expire($key, $timeOut);
        return $setRes;
    }

    /**
     * 查，获取 某键对应的值，不存在返回false
     * @param $key ,键值
     * @return bool|string ，查询成功返回信息，失败返回false
     */
    public function get($key)
    {
        $setRes = $this->redisObj[$this->sn]->get($key);//不存在返回false
        if ($setRes === 'false') {
            return false;
        }
        return $setRes;
    }
    /*------------------------------------1.end string结构----------------------------------------------------*/


    /*------------------------------------2.start list结构----------------------------------------------------*/
    /**
     * 增，构建一个列表(先进后去，类似栈)
     * @param String $key KEY名称
     * @param string $value 值
     * @param $timeOut |num  过期时间
     * @return mixed
     */
    public function lPush($key, $value, $timeOut = 0)
    {
//          echo "$key - $value \n";
        $re = $this->redisObj[$this->sn]->LPUSH($key, $value);
        if ($timeOut > 0) $this->redisObj[$this->sn]->expire($key, $timeOut);
        return $re;
    }

    /**
     * 增，构建一个列表(先进先去，类似队列)
     * @param string $key KEY名称
     * @param string $value 值
     * @param $timeOut |num  过期时间
     * @return mixed
     */
    public function rPush($key, $value, $timeOut = 0)
    {
//          echo "$key - $value \n";
        $re = $this->redisObj[$this->sn]->RPUSH($key, $value);
        if ($timeOut > 0) $this->redisObj[$this->sn]->expire($key, $timeOut);
        return $re;
    }

    /**
     * 查，获取所有列表数据（从头到尾取）
     * @param string $key KEY名称
     * @param int $head 开始
     * @param int $tail 结束
     * @return mixed
     */
    public function lRanges($key, $head, $tail)
    {
        return $this->redisObj[$this->sn]->lrange($key, $head, $tail);
    }

    /**
     * Power by Mikkle
     * QQ:776329498
     * @param $key
     * @return mixed
     */

    public function rPop($key)
    {
        return $this->redisObj[$this->sn]->rPop($key);
    }

    public function lPop($key)
    {
        return $this->redisObj[$this->sn]->lpop($key);
    }


    /**
     * 列表元素数量
     * @param string $key 集合名字
     * @return mixed
     */
    public function llen($key)
    {
        $re = $this->redisObj[$this->sn]->exists($key);//存在返回1，不存在返回0
        if (!$re) return false;
        return $this->redisObj[$this->sn]->llen($key);
    }

    /**
     * 删除列表元素
     * @return mixed
     */
    public function lRem($key, $count, $value)
    {
        $re = $this->redisObj[$this->sn]->exists($key);//存在返回1，不存在返回0
        if (!$re) return false;
        return $this->redisObj[$this->sn]->lrem($key, $count, $value);
    }


    /*------------------------------------2.end list结构----------------------------------------------------*/


    /*------------------------------------3.start set结构----------------------------------------------------*/

    /**
     * 增，构建一个集合(无序集合)
     * @param string $key 集合Y名称
     * @param string|array $value 值
     * @param int $timeOut 时间  0表示无过期时间
     * @return mixed
     */
    public function sAdd($key, $value, $timeOut = 0)
    {
        $re = $this->redisObj[$this->sn]->sadd($key, $value);
        if ($timeOut > 0) $this->redisObj[$this->sn]->expire($key, $timeOut);
        return $re;
    }

    /**
     * 查，取集合对应元素
     * @param string $key 集合名字
     * @return mixed
     */
    public function sMembers($key)
    {
        $re = $this->redisObj[$this->sn]->exists($key);//存在返回1，不存在返回0
        if (!$re) return false;
        return $this->redisObj[$this->sn]->smembers($key);
    }


    /**
     * 判断 member 元素是否是集合 key 的成员
     * @param $key
     * @param $value
     * @return bool
     */
    public function sIsMember($key, $value)
    {
        $re = $this->redisObj[$this->sn]->exists($key);//存在返回1，不存在返回0
        if (!$re) return false;
        return $this->redisObj[$this->sn]->sismember($key, $value);
    }


    /**
     * 删除集合中一个元素
     * @param $key
     * @param $value
     * @return bool
     */
    public function sRem($key, $value)
    {
        $re = $this->redisObj[$this->sn]->exists($key);//存在返回1，不存在返回0
        if (!$re) return false;
        return $this->redisObj[$this->sn]->srem($key, $value);
    }


    /*------------------------------------3.end  set结构----------------------------------------------------*/


    /*------------------------------------4.start sort set结构----------------------------------------------------*/
    /*
     * 增，改，删，构建一个集合(有序集合),支持批量写入,更新
     * @param string $key 集合名称
     * @param array $score_value key为scoll, value为该权的值
     * @return int 插入操作成功返回插入数量【,更新操作返回0】
     */
    public function zadd($key, $score_value, $timeOut = 0)
    {
        if (!is_array($score_value)) return false;
        $a = 0;//存放插入的数量
        foreach ($score_value as $score => $value) {
            $re = $this->redisObj[$this->sn]->zadd($key, $score, $value);//当修改时，可以修改，但不返回更新数量
            $re && $a += 1;
            if ($timeOut > 0) $this->redisObj[$this->sn]->expire($key, $timeOut);
        }
        return $a;
    }

    /**
     * 查，有序集合查询，可升序降序,默认从第一条开始，查询一条数据
     * @param $key ,查询的键值
     * @param $min ,从第$min条开始
     * @param $max，查询的条数
     * @param $order ，asc表示升序排序，desc表示降序排序
     * @return array|bool 如果成功，返回查询信息，如果失败返回false
     */
    public function zRange($key, $min = 0, $num = 1, $order = 'desc')
    {
        $re = $this->redisObj[$this->sn]->exists($key);//存在返回1，不存在返回0
        if (!$re) return false;//不存在键值
        if ('desc' == strtolower($order)) {
            $re = $this->redisObj[$this->sn]->zrevrange($key, $min, $min + $num - 1);
        } else {
            $re = $this->redisObj[$this->sn]->zrange($key, $min, $min + $num - 1);
        }
        if (!$re) return false;//查询的范围值为空
        return $re;
    }

    /**
     * 返回集合key中，成员member的排名
     * @param $key，键值
     * @param $member，scroll值
     * @param $type ,是顺序查找还是逆序
     * @return bool,键值不存在返回false，存在返回其排名下标
     */
    public function zrank($key, $member, $type = 'desc')
    {
        $type = strtolower(trim($type));
        if ($type == 'desc') {
            $re = $this->redisObj[$this->sn]->zrevrank($key, $member);//其中有序集成员按score值递减(从大到小)顺序排列，返回其排位
        } else {
            $re = $this->redisObj[$this->sn]->zrank($key, $member);//其中有序集成员按score值递增(从小到大)顺序排列，返回其排位
        }
        if (!is_numeric($re)) return false;//不存在键值
        return $re;
    }

    /**
     * 返回名称为key的zset中score >= star且score <= end的所有元素
     * @param $key
     * @param $star，
     * @param $end ,
     * @return array
     */
    public function zrangbyscore($key, $star, $end)
    {
        return $this->redisObj[$this->sn]->ZRANGEBYSCORE($key, $star, $end);
    }


    /**
     *删除名称为key的zset中元素member
     * @param $key
     * @param $member
     * @return mixed
     */
    public function zrem($key, $member)
    {

        return $this->redisObj[$this->sn]->zRem($key, $member);
    }

    /**
     * 返回名称为key的zset中元素member的score
     * @param $key
     * @param $member
     * @return string ,返回查询的member值
     */
    public function zScore($key, $member)
    {
        return $this->redisObj[$this->sn]->ZSCORE($key, $member);
    }

    /*------------------------------------4.end sort set结构----------------------------------------------------*/


    /*------------------------------------5.hash结构----------------------------------------------------*/

    public function hSetJson($redis_key, $field, $data, $timeOut = 0)
    {
        $redis_info = json_encode($data);                           //field的数据value，以json的形式存储
        $re = $this->redisObj[$this->sn]->hSet($redis_key, $field, $redis_info);//存入缓存
        if ($timeOut > 0) $this->redisObj[$this->sn]->expire($redis_key, $timeOut);//设置过期时间
        return $re;
    }

    public function hGetJson($redis_key, $field)
    {
        $info = $this->redisObj[$this->sn]->hget($redis_key, $field);
        if ($info) {
            $info = json_decode($info, true);
        } else {
            $info = false;
        }
        return $info;
    }

    public function hSet($redis_key, $name, $data, $timeOut = 0)
    {
        $re = $this->redisObj[$this->sn]->hset($redis_key, $name, $data);
        if ($timeOut > 0) $this->redisObj[$this->sn]->expire($redis_key, $timeOut);
        return $re;
    }

    public function hSetNx($redis_key, $name, $data, $timeOut = 0)
    {
        $re = $this->redisObj[$this->sn]->hsetNx($redis_key, $name, $data);
        if ($timeOut > 0) $this->redisObj[$this->sn]->expire($redis_key, $timeOut);
        return $re;
    }


    /**
     * 增，普通逻辑的插入hash数据类型的值
     * @param $key ,键名
     * @param $data |array 一维数组，要存储的数据
     * @param $timeOut |num  过期时间
     * @return $number 返回OK【更新和插入操作都返回ok】
     */
    public function hMset($key, $data, $timeOut = 0)
    {
        $re = $this->redisObj[$this->sn]->hmset($key, $data);
        if ($timeOut > 0) $this->redisObj[$this->sn]->expire($key, $timeOut);
        return $re;
    }

    /**
     * 查，普通的获取值
     * @param $key ,表示该hash的下标值
     * @return array 。成功返回查询的数组信息，不存在信息返回false
     */
    public function hVals($key)
    {
        $re = $this->redisObj[$this->sn]->exists($key);//存在返回1，不存在返回0
        if (!$re) return false;
        $vals = $this->redisObj[$this->sn]->hvals($key);
        $keys = $this->redisObj[$this->sn]->hkeys($key);
        $re = array_combine($keys, $vals);
        foreach ($re as $k => $v) {
            if (!is_null(json_decode($v))) {
                $re[$k] = json_decode($v, true);//true表示把json返回成数组
            }
        }
        return $re;
    }

    /**
     *
     * @param $key
     * @param $filed
     * @return bool|string
     */
    public function hGet($key, $filed = [])
    {
        if (empty($filed)) {
            $re = $this->redisObj[$this->sn]->hgetAll($key);
        } elseif (is_string($filed)) {
            $re = $this->redisObj[$this->sn]->hget($key, $filed);
        } elseif (is_array($filed)) {
            $re = $this->redisObj[$this->sn]->hMget($key, $filed);
        }
        if (!$re) {
            return false;
        }
        return $re;
    }

    public function hDel($redis_key, $name)
    {
        $re = $this->redisObj[$this->sn]->hdel($redis_key, $name);
        return $re;
    }

    public function hLan($redis_key)
    {
        $re = $this->redisObj[$this->sn]->hLen($redis_key);
        return $re;
    }

    public function hIncre($redis_key, $filed, $value = 1)
    {
        return $this->redisObj[$this->sn]->hIncrBy($redis_key, $filed, $value);
    }

    /**
     * 检验某个键值是否存在
     * @param $keys keys
     * @param string $type 类型，默认为常规
     * @param string $field 若为hash类型，输入$field
     * @return bool
     */
    public function hExists($keys, $field = '')
    {
        $re = $this->redisObj[$this->sn]->hexists($keys, $field);//有返回1，无返回0
        return $re;
    }



    /*------------------------------------end hash结构----------------------------------------------------*/


    /*------------------------------------其他结构----------------------------------------------------*/
    /**
     * 设置自增,自减功能
     * @param $key ，要改变的键值
     * @param int $num ，改变的幅度，默认为1
     * @param string $member ，类型是zset或hash，需要在输入member或filed字段
     * @param string $type，类型，default为普通增减 ,还有:zset,hash
     * @return bool|int 成功返回自增后的scroll整数，失败返回false
     */
    public function incre($key, $num = 1, $member = '', $type = '')
    {
        $num = intval($num);
        switch (strtolower(trim($type))) {
            case "zset":
                $re = $this->redisObj[$this->sn]->zIncrBy($key, $num, $member);//增长权值
                break;
            case "hash":
                $re = $this->redisObj[$this->sn]->hincrby($key, $member, $num);//增长hashmap里的值
                break;
            default:
                if ($num > 0) {
                    $re = $this->redisObj[$this->sn]->incrby($key, $num);//默认增长
                } else {
                    $re = $this->redisObj[$this->sn]->decrBy($key, -$num);//默认增长
                }
                break;
        }
        if ($re) return $re;
        return false;
    }


    /**
     * 清除缓存
     * @param int $type 默认为0，清除当前数据库；1表示清除所有缓存
     */
    function flush($type = 0)
    {
        if ($type) {
            $this->redisObj[$this->sn]->flushAll();//清除所有数据库
        } else {
            $this->redisObj[$this->sn]->flushdb();//清除当前数据库
        }
    }

    /**
     * 检验某个键值是否存在
     * @param $keys keys
     * @param string $type 类型，默认为常规
     * @param string $field 若为hash类型，输入$field
     * @return bool
     */
    public function exists($keys, $type = '', $field = '')
    {
        switch (strtolower(trim($type))) {
            case 'hash':
                $re = $this->redisObj[$this->sn]->hexists($keys, $field);//有返回1，无返回0
                break;
            default:
                $re = $this->redisObj[$this->sn]->exists($keys);
                break;
        }
        return $re;
    }

    /**
     * 删除缓存
     * @param string|array $key 键值
     * @param $type 类型 默认为常规，还有hash,zset
     * @param string $field ,hash=>表示$field值，set=>表示value,zset=>表示value值，list类型特殊暂时不加
     * @return int |  返回删除的个数
     */
    public function delete($key, $type = "default", $field = '')
    {
        switch (strtolower(trim($type))) {
            case 'hash':
                $re = $this->redisObj[$this->sn]->hDel($key, $field);//返回删除个数
                break;
            case 'set':
                $re = $this->redisObj[$this->sn]->sRem($key, $field);//返回删除个数
                break;
            case 'zset':
                $re = $this->redisObj[$this->sn]->zDelete($key, $field);//返回删除个数
                break;
            default:
                $re = $this->redisObj[$this->sn]->del($key);//返回删除个数
                break;
        }
        return $re;
    }

    //日志记录
    public function logger($log_content, $position = 'user')
    {
        $max_size = 1000000;   //声明日志的最大尺寸1000K

        $log_dir = './log';//日志存放根目录

        if (!file_exists($log_dir)) mkdir($log_dir, 0777);//如果不存在该文件夹，创建

        if ($position == 'user') {
            $log_filename = "{$log_dir}/User_redis_log.txt";  //日志名称
        } else {
            $log_filename = "{$log_dir}/Wap_redis_log.txt";  //日志名称
        }

        //如果文件存在并且大于了规定的最大尺寸就删除了
        if (file_exists($log_filename) && (abs(filesize($log_filename)) > $max_size)) {
            unlink($log_filename);
        }

        //写入日志，内容前加上时间， 后面加上换行， 以追加的方式写入
        file_put_contents($log_filename, date('Y-m-d_H:i:s') . " " . $log_content . "\n", FILE_APPEND);
    }


    function flushDB()
    {
        $this->redisObj[$this->sn]->flushDB();
    }

    function __destruct()
    {
        $this->redisObj[$this->sn]->close();
    }

    /**
     * 魔术方法 有不存在的操作的时候执行
     * @access public
     * @param string $method 方法名
     * @param array $args 参数
     * @return mixed
     */
    public function __call($method, $args)
    {
        call_user_func_array([$this->redisObj[$this->sn], $method], $args);
    }

}