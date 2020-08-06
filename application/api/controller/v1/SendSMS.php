<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\LogService;
use app\api\service\SendSMSService;
use app\lib\exception\SuccessMessage;
use app\lib\exception\SuccessMessageWithData;
use think\Cache;
use think\facade\Request;
use think\facade\Session;

class SendSMS extends BaseController
{
    /**
     * @api {POST} /api/v1/sms/register  MINI小程序端-发送验证手机验证码
     * @apiGroup  MINI
     * @apiVersion 1.0.1
     * @apiDescription  MINI小程序端-发送验证手机验证码
     * @apiExample {post}  请求样例:
     *    {
     *       "phone":"18956225230"
     *     }
     * @apiParam (请求参数说明) {String} phone  手机号
     * @apiSuccessExample {json} 返回样例:
     *{"msg":"ok","errorCode":0}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     */
    public function sendCodeToMINI()
    {
        $params = $this->request->param();
        (new SendSMSService())->sendCode($params['phone'], 'register');
        return json(new SuccessMessage());

    }

    /**
     * @api {POST} /api/v1/sms/login  Android司机端-登录时发送手机验证码
     * @apiGroup  Android
     * @apiVersion 1.0.1
     * @apiDescription  Android司机端-登录时发送手机验证码
     * @apiExample {post}  请求样例:
     *    {
     *       "phone":"18956225230"
     *     }
     * @apiParam (请求参数说明) {String} phone  手机号
     * @apiSuccessExample {json} 返回样例:
     *{"msg":"ok","errorCode":0}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     */
    public function sendCodeToAndroid()
    {
        $params = $this->request->param();
        (new SendSMSService())->sendCode($params['phone'], 'login');
        return json(new SuccessMessage());
    }

    /**
     * 处理没有成功发送短信
     */
    public function sendHandel()
    {
        (new SendSMSService())->sendHandel();

    }

    /**
     * 发送短信给司机
     */
    public function sendOderToDriver()
    {
        $phone = Request::param('phone');
        $order_num = Request::param('order_num');
        $create_time = Request::param('create_time');
        (new SendSMSService())->sendOrderSMS($phone, ['code' => 'OK' . $order_num,
            'order_time' => $create_time]);
    }

    /**
     * @api {GET} /api/v1/sms/records  CMS管理端-短信管理-获取短信记录(系统管理员/代理商管理员)
     * @apiGroup  CMS
     * @apiVersion 1.0.1
     * @apiDescription  CMS管理端-短信管理(系统管理员/代理商管理员)
     * @apiExample {get}  请求样例:
     * https://tonglingok.com/api/v1/sms/records?sign=ok&time_begin=2020-07-30&time_end=2020-07-31&page=1&size=1&state=3&phone
     * @apiParam (请求参数说明) {string} sign 代理sign号，系统管理员在代理列表里返回，代理商管理员传入0
     * @apiParam (请求参数说明) {string} time_begin 查询开始时间
     * @apiParam (请求参数说明) {string} time_end 查询结束时间
     * @apiParam (请求参数说明) {int} state 1,发送成功；2 发送失败；3 全部
     * @apiParam (请求参数说明) {string} phone 手机号
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":106,"per_page":"1","current_page":1,"last_page":106,"data":[{"id":8566,"sign":"ok","content":"{\"money\":\"112\",\"company\":\"OK\",\"phone\":\"19855751988\"}","create_time":"2020-07-31 01:13:30","update_time":"2020-07-31 01:13:30","state":1,"return_data":"","type":"drive_order_complete"}]}}
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} id 记录ID
     * @apiSuccess (返回参数说明) {String} phone 手机号
     * @apiSuccess (返回参数说明) {int} content 发送内容
     * @apiSuccess (返回参数说明) {int} state state 1,发送成功；2 发送失败
     * @apiSuccess (返回参数说明) {String} create_time 创建时间
     */
    public function records($sign = 0, $phone = '', $state = 3)
    {
        $params = Request::param();
        $params['sign'] = $sign;
        $params['phone'] = $phone;
        $params['state'] = $state;
        $records = (new SendSMSService())->records($params);
        return json(new  SuccessMessageWithData(['data' => $records]));
    }

    /**
     * @api {GET} /api/v1/sms/statistic CMS管理端-短信管理-短信消耗统计(系统管理员/代理商管理员)
     * @apiGroup  CMS
     * @apiVersion 1.0.1
     * @apiDescription   CMS管理端-短信管理-短信消耗统计(系统管理员/代理商管理员)
     * @apiExample {get}  请求样例:
     * https://tonglingok.com/api/v1/sms/statistic?sign=ok
     * @apiParam (请求参数说明) {string} sign 代理sign号，系统管理员在代理列表里返回，代理商管理员传入0
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"sendAll":8080,"success":8064,"fail":16,"balance":1000}}
     * @apiSuccess (返回参数说明) {int} sendAll 发送总量
     * @apiSuccess (返回参数说明) {int} success 发送成功数量
     * @apiSuccess (返回参数说明) {int} fail 发送失败数量
     * @apiSuccess (返回参数说明) {int} balance 余额
     */
    public function statistic()
    {
        $sign = Request::param('sign');
        $records = (new SendSMSService())->statistic($sign);
        return json(new  SuccessMessageWithData(['data' => $records]));
    }

    /**
     * @api {POST} /api/v1/sms/recharge/manager  CMS管理端-管理员给代理商充值短信
     * @apiGroup  CMS
     * @apiVersion 1.0.1
     * @apiDescription   CMS管理端-管理员给代理商充值短信
     * @apiExample {post}  请求样例:
     *    {
     *       "sign":"ok",
     *       "count":"1000",
     *       "money":"1000",
     *     }
     * @apiParam (请求参数说明) {int} sign  代理sign号，
     * @apiParam (请求参数说明) {int} count  充值数量
     * @apiParam (请求参数说明) {int} money  充值金额
     *  * @apiSuccessExample {json} 返回样例:
     *{"msg":"ok","errorCode":0}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     */
    public function managerRecharge()
    {
        $params = Request::param();
        (new SendSMSService())->managerRecharge($params);
        return json(new SuccessMessage());

    }

    /**
     * @api {POST} /api/v1/sms/recharge/agent  CMS管理端-代理商充值短信
     * @apiGroup  CMS
     * @apiVersion 1.0.1
     * @apiDescription   CMS管理端-代理商充值短信
     * @apiExample {post}  请求样例:
     *    {
     *       "template_id":1,
     *     }
     * @apiParam (请求参数说明) {String} template_id  充值模板
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"url":"https:\/\/tonglingok.com\/static\/qrcode\/071ed6a23cc52562d9b3f163d24a29cb81ea6377.png"}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {String} url 支付二维码地址
     */
    public function agentRecharge()
    {
        $template_id = Request::param('template_id');
        $url = (new SendSMSService())->agentRecharge($template_id);
        return json(new SuccessMessageWithData(['data' => ['url' => $url]]));

    }

    /**
     * @api {POST} /api/v1/sms/recharge/template/save  CMS管理端-充值模板管理-新增充值模板
     * @apiGroup  CMS
     * @apiVersion 1.0.1
     * @apiDescription    CMS管理端-充值模板管理-新增充值模板
     * @apiExample {post}  请求样例:
     *    {
     *       "count":1000,
     *       "price":0.08,
     *       "money":80,
     *     }
     * @apiParam (请求参数说明) {int} count  充值数量
     * @apiParam (请求参数说明) {float} price  单价,单位元
     * @apiParam (请求参数说明) {int} money  总金额
     * @apiSuccessExample {json} 返回样例:
     *{"msg":"ok","errorCode":0}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     */
    public function saveRechargeTemplate()
    {
        $params = Request::param();
        (new SendSMSService())->saveRechargeTemplate($params);
        return json(new SuccessMessage());
    }

    /**
     * @api {GET} /api/v1/sms/recharge/templates  CMS管理端-充值模板管理-获取模板列表
     * @apiGroup  CMS
     * @apiVersion 1.0.1
     * @apiDescription   CMS管理端-充值模板管理-获取模板列表
     * @apiExample {get}  请求样例:
     * https://tonglingok.com/api/v1/sms/recharge/templates?page=1&size=1
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":106,"per_page":"1","current_page":1,"last_page":106,"data":[{"id":8566,"sign":"ok","content":"{\"money\":\"112\",\"company\":\"OK\",\"phone\":\"19855751988\"}","create_time":"2020-07-31 01:13:30","update_time":"2020-07-31 01:13:30","state":1,"return_data":"","type":"drive_order_complete"}]}}
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} id 模板ID
     * @apiSuccess (返回参数说明) {int} count 充值数量
     * @apiSuccess (返回参数说明) {int} price 单价
     * @apiSuccess (返回参数说明) {int} money 总金额
     * @apiSuccess (返回参数说明) {String} create_time 创建时间
     */
    public function rechargeTemplates($page = 1, $size = 10)
    {
        $templates = (new SendSMSService())->rechargeTemplates($page, $size);
        return json(new SuccessMessageWithData(['data' => $templates]));
    }

    /**
     * @api {GET} /api/v1/sms/recharge/template  CMS管理端-充值模板管理-获取指定模板
     * @apiGroup  CMS
     * @apiVersion 1.0.1
     * @apiDescription   CMS管理端-充值模板管理-获取指定模板
     * @apiExample {get}  请求样例:
     * https://tonglingok.com/api/v1/sms/recharge/template?id=1
     * @apiParam (请求参数说明) {int} id 模板id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"id":1,"count":1000,"price":"0.08","money":"80.00","state":1,"create_time":"2020-08-01 01:53:52","update_time":"2020-08-01 01:53:57"}}
     * @apiSuccess (返回参数说明) {int} id 模板ID
     * @apiSuccess (返回参数说明) {int} count 充值数量
     * @apiSuccess (返回参数说明) {int} price 单价
     * @apiSuccess (返回参数说明) {int} money 总金额
     * @apiSuccess (返回参数说明) {String} create_time 创建时间
     */
    public function rechargeTemplate()
    {
        $id = Request::param('id');
        $template = (new SendSMSService())->rechargeTemplate($id);
        return json(new SuccessMessageWithData(['data' => $template]));
    }

    /**
     * @api {POST} /api/v1/sms/recharge/template/update  CMS管理端-充值模板管理-更新充值模板
     * @apiGroup  CMS
     * @apiVersion 1.0.1
     * @apiDescription    CMS管理端-充值模板管理-更新充值模板
     * @apiExample {post}  请求样例:
     *    {
     *       "id":1,
     *       "state":2,
     *       "count":1000,
     *       "price":0.08,
     *       "money":80,
     *     }
     * @apiParam (请求参数说明) {int} id  模板id
     * @apiParam (请求参数说明) {int} state  2：删除操作
     * @apiParam (请求参数说明) {int} count  充值数量
     * @apiParam (请求参数说明) {float} price  单价,单位元
     * @apiParam (请求参数说明) {int} money  总金额
     * @apiSuccessExample {json} 返回样例:
     *{"msg":"ok","errorCode":0}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     */
    public function updateRechargeTemplate()
    {
        $params = Request::param();
        (new SendSMSService())->updateRechargeTemplate($params);
        return json(new SuccessMessage());
    }

    /**
     * @api {GET} /api/v1/sms/recharges  CMS管理端-短信管理-获取短信充值记录(系统管理员/代理商管理员)
     * @apiGroup  CMS
     * @apiVersion 1.0.1
     * @apiDescription  CMS管理端-短信管理-获取短信充值记录(系统管理员/代理商管理员)
     * @apiExample {get}  请求样例:
     * https://tonglingok.com/api/v1/sms/recharges?sign=ok&time_begin=2020-07-30&time_end=2020-07-31&page=1&size=1
     * @apiParam (请求参数说明) {string} sign 代理sign号，系统管理员在代理列表里返回，代理商管理员传入0
     * @apiParam (请求参数说明) {string} time_begin 查询开始时间
     * @apiParam (请求参数说明) {string} time_end 查询结束时间
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":1,"per_page":"1","current_page":1,"last_page":1,"data":[{"id":1,"sign":"ok","count":1000,"money":"80.00","state":1,"create_time":"2020-08-02 01:34:30","update_time":"2020-08-02 01:34:36","template_id":1,"pay":12,"order_number":"1212121","type":2}]}}
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} id 记录ID
     * @apiSuccess (返回参数说明) {int} company 代理商
     * @apiSuccess (返回参数说明) {int} count  充值数量
     * @apiSuccess (返回参数说明) {float} price  单价,单位元
     * @apiSuccess (返回参数说明) {int} money  总金额
     * @apiSuccess (返回参数说明) {String} create_time 创建时间
     */
    public function recharges($page = 1, $size = 10, $sign = 0, $time_begin = '', $time_end = '')
    {
        $recharges = (new SendSMSService())->recharges($sign, $page, $size, $time_begin, $time_end);
        return json(new SuccessMessageWithData(['data' => $recharges]));

    }


}