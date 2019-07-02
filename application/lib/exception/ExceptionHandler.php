<?php
/**
 * Created by 七月.
 * Author: 七月
 * Date: 2017/4/24
 * Time: 3:33
 */

namespace app\lib\exception;


use think\exception\Handle;
class ExceptionHandler extends Handle
{

    private $code;
    private $msg;
    private $errorCode;

    // 需要返回客户端当前请求的URL路径

    public function render(\Exception $e)
    {
        if ($e instanceof BaseException) {
            //如果是自定义的异常
            $this->code = $e->code;
            $this->msg = $e->msg;
            $this->errorCode = $e->errorCode;
        } else {
            if (config('app_debug')) {
                return parent::render($e);
            } else {
                $this->code = 500;
                $this->msg = '服务器内部错误';
                $this->errorCode = 999;
                //$this->recordErrorLog($e);
            }
        }


        //$request = Request::instance();
        $result = [
            'msg' => $this->msg,
            'errorCode' => $this->errorCode,
            //'request_url' => $request->url()
        ];

        return json($result, $this->code);
    }
}