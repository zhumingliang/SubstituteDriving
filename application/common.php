<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件


function getRandChar($length)
{
    $str = null;
    $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
    $max = strlen($strPol) - 1;

    for ($i = 0;
         $i < $length;
         $i++) {
        $str .= $strPol[rand(0, $max)];
    }

    return $str;
}


function getSkuID($length = 12)
{
    $str = null;
    $strPol = "0123456789abcdefghijklmnopqrstuvwxyz";
    $max = strlen($strPol) - 1;

    for ($i = 0;
         $i < $length;
         $i++) {
        $str .= $strPol[rand(0, $max)];
    }

    return $str;
}

/**
 * 创建guid
 * @return string
 */
function guid()
{
    mt_srand((double)microtime() * 10000);
    $charid = strtoupper(md5(uniqid(rand(), true)));
    $hyphen = chr(45);
    $uuid = substr($charid, 0, 8) . $hyphen . substr($charid, 8, 4) . $hyphen . substr($charid, 12, 4) . $hyphen . substr($charid, 16, 4) . $hyphen . substr($charid, 20, 12);
    return $uuid;
}


/**
 * base64转图片
 * @param $base64
 * @return string
 * @throws Exception
 */
function base64toImg($base64)
{
    try {
        // $base64 = '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';

        if (empty($base64)) {
            return '';
        }
        $path = dirname($_SERVER['SCRIPT_FILENAME']) . '/static/imgs';
        if (!is_dir($path)) {
            mkdir(iconv("UTF-8", "GBK", $path), 0777, true);
        }
        $img = base64_decode($base64);
        $name = guid();
        $imgUrl = $path . '/' . $name . '.jpg';
        $a = file_put_contents($imgUrl, $img);
        $imgUrl2 = 'static/imgs/' . $name . '.jpg';
        return $a ? $imgUrl2 : '';
    } catch (Exception $e) {
        throw $e;
    }

}


/**
 * 日期加上指定天数
 * @param $count
 * @param $time_old
 * @return false|string
 */
function addDay($count, $time_old)
{
    $time_new = date('Y-m-d', strtotime('+' . $count . ' day',
        strtotime($time_old)));
    return $time_new;

}

/**
 * 日期减去指定天数
 * @param $count
 * @param $time_old
 * @return false|string
 */
function reduceDay($count, $time_old)
{
    $time_new = date('Y-m-d', strtotime('-' . $count . ' day',
        strtotime($time_old)));
    return $time_new;

}

/**
 * 生成订单号
 * @return string
 */
function makeOrderNo()
{
    $yCode = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J');
    $orderSn =
        $yCode[intval(date('Y')) - 2017] . strtoupper(dechex(date('m'))) . date(
            'd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf(
            '%02d', rand(0, 99));
    return $orderSn;
}

function curl_file_get_contents($durl,$headers){

    // 初始化
    $curl = curl_init();
    // 设置url路径
    curl_setopt($curl, CURLOPT_URL, $durl);
    // 将 curl_exec()获取的信息以文件流的形式返回，而不是直接输出。
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true) ;
    // 在启用 CURLOPT_RETURNTRANSFER 时候将获取数据返回
    curl_setopt($curl, CURLOPT_BINARYTRANSFER, true) ;
    // 添加头信息
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    // CURLINFO_HEADER_OUT选项可以拿到请求头信息
    curl_setopt($curl, CURLINFO_HEADER_OUT, true);
    // 不验证SSL
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
    // 执行
    $data = curl_exec($curl);
    // 打印请求头信息
//        echo curl_getinfo($curl, CURLINFO_HEADER_OUT);
    // 关闭连接
    curl_close($curl);
    // 返回数据
    return $data;
}




