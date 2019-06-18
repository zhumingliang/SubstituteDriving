<?php
/**
 * Created by PhpStorm.
 * User: zhumingliang
 * Date: 2018/3/20
 * Time: 下午1:24
 */


namespace app\api\behavior;

use app\api\service\FlowService;
use app\api\service\Token;
use app\lib\enum\BookingReportEnum;
use Rollbar\Rollbar;
use think\facade\Request;


class CORS
{
    public function appInit($params)
    {

        //解决跨域
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Headers: token,Origin, X-Requested-With, Content-Type, Accept");
        header('Access-Control-Allow-Methods: POST,GET');
        if (request()->isOptions()) {
            exit();
        }
    }
}