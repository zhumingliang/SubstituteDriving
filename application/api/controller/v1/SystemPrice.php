<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\model\FarStateT;
use app\api\model\StartPriceT;
use app\api\model\SystemOrderChargeT;
use app\api\model\TimeIntervalT;
use app\api\model\WaitPriceT;
use app\api\model\WeatherT;
use app\api\service\SystemPriceService;
use app\lib\enum\CommonEnum;
use app\lib\exception\SuccessMessage;
use app\lib\exception\SuccessMessageWithData;
use app\lib\exception\UpdateException;
use think\facade\Request;

class SystemPrice extends BaseController
{

    /**
     * @api {POST} /api/v1/SystemPrice/start/save  Android管理端-新增起步价设置/新增远程接驾设置
     * @apiGroup  Android
     * @apiVersion 1.0.1
     * @apiDescription  Android管理端-新增起步价设置/新增远程接驾设置
     * @apiExample {post}  请求样例:
     *    {
     *       "distance":8.0,
     *       "price": 18.0,
     *       "order": 1,
     *       "type": 1
     *     }
     * @apiParam (请求参数说明) {float} distance  距离
     * @apiParam (请求参数说明) {float} price    价格
     * @apiParam (请求参数说明) {int} order   价格设置排序：1 | 起步价，后面按序号排序
     * @apiParam (请求参数说明) {int} type  类别：1 |  起步价设置；2 | 远程接驾设置
     *
     * @apiSuccessExample {json} 返回样例:
     *{"msg":"ok","errorCode":0}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     */
    public function startSave()
    {
        $params = $this->request->param();
        $params['state'] = CommonEnum::STATE_IS_OK;
        $params['company_id'] = \app\api\service\Token::getCurrentTokenVar('company_id');
        //$params['area'] = '铜陵';
        $start = StartPriceT::create($params);
        if (!$start) {
            throw new UpdateException();
        }
        return json(new SuccessMessage());
    }

    /**
     * @api {POST} /api/v1/SystemPrice/start/handel  Android管理端-操作起步价设置/远程接驾状态（删除明细）
     * @apiGroup  Android
     * @apiVersion 1.0.1
     * @apiDescription  Android管理端操作起步价设置/远程接驾状态
     * @apiExample {POST}  请求样例:
     * {
     * "id": 1
     * }
     * @apiParam (请求参数说明) {int} id 明细id
     * @apiSuccessExample {json} 返回样例:
     * {"msg": "ok","errorCode": 0}
     * @apiSuccess (返回参数说明) {int} errorCode 错误代码 0 表示没有错误
     * @apiSuccess (返回参数说明) {String} msg 操作结果描述
     *
     */
    public function startHandel()
    {
        $params = $this->request->param();
        $id = StartPriceT::update(['state' => CommonEnum::STATE_IS_FAIL], ['id' => $params['id']]);
        if (!$id) {
            throw new UpdateException();
        }
        return json(new SuccessMessage());

    }

    /**
     * @api {POST} /api/v1/SystemPrice/start/open/handel  Android管理端-远程接驾状态操作（启用/停用）
     * @apiGroup  Android
     * @apiVersion 1.0.1
     * @apiDescription  Android管理端操作起步价设置/远程接驾状态
     * @apiExample {POST}  请求样例:
     * {
     * "state": 1
     * }
     * @apiParam (请求参数说明) {int} state 状态: 1 | 启用；2|停用
     * @apiSuccessExample {json} 返回样例:
     * {"msg": "ok","errorCode": 0}
     * @apiSuccess (返回参数说明) {int} errorCode 错误代码 0 表示没有错误
     * @apiSuccess (返回参数说明) {String} msg 操作结果描述
     *
     */
    public function startOpenHandel()
    {
        $state = $this->request->param('state');
        $company_id = \app\api\service\Token::getCurrentTokenVar('company_id');
        $id = FarStateT::update(['state' => $state], ['company_id' => $company_id]);
        if (!$id) {
            throw new UpdateException();
        }
        return json(new SuccessMessage());

    }

    /**
     * @api {GET} /api/v1/SystemPrice/start  Android管理端-获取起步价设置/远程接驾设置信息
     * @apiGroup   Android
     * @apiVersion 1.0.1
     * @apiDescription   Android管理端-获取起步价设置/远程接驾设置信息
     * @apiExample {get}  请求样例:
     * https://tonglingok.com/api/v1/SystemPrice/start?type=2
     * @apiParam (请求参数说明) {int} type 类别：1 |  起步价设置；2 | 远程接驾设置
     * @apiSuccessExample {json} 获取起步价设置返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":[{"id":1,"distance":8,"price":30,"order":1,"create_time":"2019-07-02 18:49:03","update_time":"2019-07-02 23:47:58"},{"id":2,"distance":1,"price":80,"order":2,"create_time":"2019-07-02 18:52:19","update_time":"2019-07-02 23:47:58"},{"id":3,"distance":2,"price":15,"order":3,"create_time":"2019-07-02 18:54:22","update_time":"2019-07-02 18:54:22"}]}
     * @apiSuccess (返回参数说明) {int} id 设置id
     * @apiSuccess (返回参数说明) {float} distance 里程
     * @apiSuccess (返回参数说明) {float} price 价格
     * @apiSuccess (返回参数说明) {int} order 排序
     * @apiSuccess (返回参数说明) {String} create_time  创建时间
     * @apiSuccess (返回参数说明) {String} update_time 修改时间
     * @apiSuccessExample {json} 获取远程接驾设置返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"open":1,"info":[{"id":4,"distance":10,"price":18,"order":1,"create_time":"2019-07-02 18:57:29","update_time":"2019-07-02 18:57:29"},{"id":5,"distance":1,"price":10,"order":2,"create_time":"2019-07-02 18:57:42","update_time":"2019-07-02 18:57:42"},{"id":6,"distance":1,"price":15,"order":3,"create_time":"2019-07-02 18:57:55","update_time":"2019-07-02 18:57:55"}]}}
     * @apiSuccess (返回参数说明) {int} id 设置id
     * @apiSuccess (返回参数说明) {int} open 是否开启：1 | 开启；2| 关闭
     * @apiSuccess (返回参数说明) {Obj} info 远程接驾设置信息
     * @apiSuccess (返回参数说明) {float} distance 里程
     * @apiSuccess (返回参数说明) {float} price 价格
     * @apiSuccess (返回参数说明) {String} create_time  创建时间
     * @apiSuccess (返回参数说明) {String} update_time 修改时间
     * @apiSuccess (返回参数说明) {int} order 排序
     */
    public function startPrice()
    {
        $params = $this->request->param();
        $type = $params['type'];
        $company_id = \app\api\service\Token::getCurrentTokenVar('company_id');

        $info = StartPriceT::where('company_id', $company_id)
            ->where('type', $type)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->hidden(['type', 'state', 'area'])
            ->order('order')
            ->select();

        if ($type == 2) {
            $far = FarStateT::where('company_id', $company_id)->find();
            $open = $far['open'];
            $info = [
                'open' => $open,
                'info' => $info
            ];
        }
        return json(new SuccessMessageWithData(['data' => $info]));
    }

    /**
     * @api {POST} /api/v1/SystemPrice/start/update  Android管理端-修改起步价设置/新增远程接驾设置
     * @apiGroup  Android
     * @apiVersion 1.0.1
     * @apiDescription  Android管理端-修改起步价设置/新增远程接驾设置
     * @apiExample {post}  请求样例:
     *    {
     *       "id":8.0,
     *       "distance":8.0,
     *       "price": 18.0,
     *       "order": 1
     *     }
     * @apiParam (请求参数说明) {id} id    明细ID
     * @apiParam (请求参数说明) {float} distance  距离
     * @apiParam (请求参数说明) {float} price    价格
     * @apiParam (请求参数说明) {int} order   价格设置排序：1 | 起步价，后面按序号排序*
     * @apiSuccessExample {json} 返回样例:
     *{"msg":"ok","errorCode":0}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     */
    public function startUpdate()
    {
        /* $info = $this->request->param('info');
         (new SystemPriceService())->startUpdate($info);
         return json(new SuccessMessage());*/
        $params = Request::only('id,distance,price,order');
        (new SystemPriceService())->startUpdate($params);
        return json(new SuccessMessage());

    }

    /**
     * @api {POST} /api/v1/SystemPrice/interval/save  Android管理端-新增分时段计费设置
     * @apiGroup  Android
     * @apiVersion 1.0.1
     * @apiDescription  Android管理端-新增分时段计费设置
     * @apiExample {post}  请求样例:
     *    {
     *       "price":8.0,
     *       "time_begin": "09:00",
     *       "time_end": "12:00"
     *     }
     * @apiParam (请求参数说明) {float} price    价格
     * @apiParam (请求参数说明) {String} time_begin   开始时间
     * @apiParam (请求参数说明) {String} time_end  截止时间
     *
     * @apiSuccessExample {json} 返回样例:
     *{"msg":"ok","errorCode":0}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     */
    public function intervalSave()
    {
        $company_id = \app\api\service\Token::getCurrentTokenVar('company_id');
        $params = $this->request->param();
        $params['state'] = CommonEnum::STATE_IS_OK;
        $params['company_id'] = $company_id;
        $start = TimeIntervalT::create($params);
        if (!$start) {
            throw new UpdateException();
        }
        return json(new SuccessMessage());

    }

    /**
     * @api {POST} /api/v1/SystemPrice/interval/handel  Android管理端-操作分时段计费设置状态（删除明细）
     * @apiGroup  Android
     * @apiVersion 1.0.1
     * @apiDescription  Android管理端操作分时段计费设置状态（删除明细
     * @apiExample {POST}  请求样例:
     * {
     * "id": 1
     * }
     * @apiParam (请求参数说明) {int} id 明细id
     * @apiSuccessExample {json} 返回样例:
     * {"msg": "ok","errorCode": 0}
     * @apiSuccess (返回参数说明) {int} errorCode 错误代码 0 表示没有错误
     * @apiSuccess (返回参数说明) {String} msg 操作结果描述
     *
     */
    public function intervalHandel()
    {
        $params = $this->request->param();
        $id = TimeIntervalT::update(['state' => CommonEnum::STATE_IS_FAIL], ['id' => $params['id']]);
        if (!$id) {
            throw new UpdateException();
        }
        return json(new SuccessMessage());

    }

    /**
     * @api {GET} /api/v1/SystemPrice/interval  Android管理端-获取分时段计费设置
     * @apiGroup   Android
     * @apiVersion 1.0.1
     * @apiDescription  Android管理端-获取分时段计费设置
     * @apiExample {get}  请求样例:
     * https://tonglingok.com/api/v1/SystemPrice/interval
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":[{"id":1,"time_begin":"08:00","time_end":"18:00","price":18},{"id":2,"time_begin":"18:00","time_end":"00:00","price":18}]}
     * @apiSuccess (返回参数说明) {int} id 设置id
     * @apiSuccess (返回参数说明) {float} price 价格
     * @apiSuccess (返回参数说明) {Strings} time_begin 开始时间
     * @apiSuccess (返回参数说明) {Strings} time_end 结束时间
     */
    public function intervalPrice()
    {
        $company_id = \app\api\service\Token::getCurrentTokenVar('company_id');
        $info = TimeIntervalT::where('company_id', $company_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->hidden(['state', 'create_time', 'update_time', 'area'])
            ->order('create_time')
            ->select();
        return json(new SuccessMessageWithData(['data' => $info]));
    }

    /**
     * @api {POST} /api/v1/SystemPrice/interval/update  Android管理端-修改分时段计费设置
     * @apiGroup  Android
     * @apiVersion 1.0.1
     * @apiDescription Android管理端-修改分时段计费设置
     * @apiExample {post}  请求样例:
     *    {
     *       "id":1,
     *       "price":8.0,
     *       "time_begin": "09:00",
     *       "time_end": "12:00"
     *     }
     * @apiParam (请求参数说明) {int} id    设置ID
     * @apiParam (请求参数说明) {float} price    价格
     * @apiParam (请求参数说明) {String} time_begin   开始时间
     * @apiParam (请求参数说明) {String} time_end  截止时间
     *
     * @apiSuccessExample {json} 返回样例:
     *{"msg":"ok","errorCode":0}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     */
    public function intervalUpdate()
    {
        $params = $this->request->param();
        (new SystemPriceService())->intervalUpdate($params);
        return json(new SuccessMessage());

    }

    /**
     * @api {GET} /api/v1/SystemPrice/wait  Android管理端-获取等待时间设置信息
     * @apiGroup   Android
     * @apiVersion 1.0.1
     * @apiDescription   Android管理端-获取等待时间设置信息
     * @apiExample {get}  请求样例:
     * https://tonglingok.com/api/v1/SystemPrice/wait
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"id":1,"free":30,"price":1}}     * @apiSuccess (返回参数说明) {int} id 设置id
     * @apiSuccess (返回参数说明) {int} id 设置id
     * @apiSuccess (返回参数说明) {int} free 免费时长，单位：分钟
     * @apiSuccess (返回参数说明) {int} price 价格 ，单位：元/分钟
     */
    public function waitPrice()
    {
        $company_id = \app\api\service\Token::getCurrentTokenVar('company_id');
        $info = WaitPriceT::where('company_id', $company_id)
            ->field('id,free,price')
            ->find();
        return json(new SuccessMessageWithData(['data' => $info]));
    }

    /**
     * @api {POST} /api/v1/SystemPrice/wait/update  Android管理端-修改等待时间设置
     * @apiGroup  Android
     * @apiVersion 1.0.1
     * @apiDescription Android管理端-修改分时段计费设置
     * @apiExample {post}  请求样例:
     *    {
     *       "id":1,
     *       "free":30,
     *       "price":1
     *     }
     * @apiParam (请求参数说明) {int} id  设置ID
     * @apiParam (请求参数说明) {String} free   免费时长，单位：分钟
     * @apiParam (请求参数说明) {float} price   价格 ，单位：元/分钟
     * @apiSuccessExample {json} 返回样例:
     *{"msg":"ok","errorCode":0}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     */
    public function waitUpdate()
    {
        $info = $this->request->param();
        if (empty($info['id'])) {
            $info['company_id'] = \app\api\service\Token::getCurrentTokenVar('company_id');
            $res = WaitPriceT::create($info);
        } else {
            $res = (new WaitPriceT())->isUpdate()->save($info);
        }
        if (!$res) {
            throw  new UpdateException();
        }
        return json(new SuccessMessage());

    }

    /**
     * @api {GET} /api/v1/SystemPrice/weather  Android管理端-获取恶劣天气补助设置信息
     * @apiGroup   Android
     * @apiVersion 1.0.1
     * @apiDescription   Android管理端-获取恶劣天气补助设置信息
     * @apiExample {get}  请求样例:
     * https://tonglingok.com/api/v1/SystemPrice/weather
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"id":1,"ratio":1.3,"state":1,"create_time":"2019-07-03 01:20:03","update_time":"2019-07-03 01:30:38"}}
     * @apiSuccess (返回参数说明) {int} id 设置id
     * @apiSuccess (返回参数说明) {float} ratio 比例
     * @apiSuccess (返回参数说明) {int} state 状态：1 | 启用；2 | 停用
     * @apiSuccess (返回参数说明) {String} create_time  创建时间
     * @apiSuccess (返回参数说明) {String} update_time 修改时间
     */
    public function weatherPrice()
    {
        $company_id = \app\api\service\Token::getCurrentTokenVar('company_id');
        $info = WeatherT::where('company_id', $company_id)
            ->field('id,ratio,state,create_time,update_time')
            ->find();
        return json(new SuccessMessageWithData(['data' => $info]));
    }

    /**
     * @api {POST} /api/v1/SystemPrice/weather/update  Android管理端-修改恶劣天气补助设置信息(比例设置/状态设置)
     * @apiGroup  Android
     * @apiVersion 1.0.1
     * @apiDescription Android管理端-修改恶劣天气补助设置信息
     * @apiExample {post}  请求样例:
     *    {
     *       "id":1,
     *       "ratio":1.3
     *       "state":1
     *     }
     * @apiParam (请求参数说明) {int} id  设置ID
     * @apiParam (请求参数说明) {float} ratio   比例
     * @apiParam (请求参数说明) {int} state   状态：1 | 启用；2 | 停用
     * @apiSuccessExample {json} 返回样例:
     *{"msg":"ok","errorCode":0}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     */
    public function weatherUpdate()
    {
        $info = $this->request->param();
        if (empty($info['id'])) {
            $info['company_id'] = \app\api\service\Token::getCurrentTokenVar('company_id');
            $res = WeatherT::create($info);

        } else {
            $res = (new WeatherT())->isUpdate()->save($info);
        }
        if (!$res) {
            throw  new UpdateException();
        }
        return json(new SuccessMessage());

    }

    /**
     * @api {GET} /api/v1/SystemPrice/initPrice/mini  小程序用户端-获取初始化价格设置信息
     * @apiGroup   MINI
     * @apiVersion 1.0.1
     * @apiDescription   小程序用户端-获取初始化价格设置信息
     * @apiExample {get}  请求样例:
     * https://tonglingok.com/api/v1/SystemPrice/initPrice/mini
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"start":[{"id":1,"distance":8,"price":30,"order":1},{"id":2,"distance":1,"price":80,"order":2},{"id":3,"distance":2,"price":15,"order":3}],"wait":{"id":1,"free":30,"price":1},"interval":[{"id":1,"time_begin":"08:00:00","time_end":"18:00:00","price":18},{"id":2,"time_begin":"18:00:00","time_end":"00:00:00","price":18}]}}
     * @apiSuccess (返回参数说明) {Obj} start  价格信息
     * @apiSuccess (返回参数说明) {int} start-id  价格设置id
     * @apiSuccess (返回参数说明) {float} start-distance  距离
     * @apiSuccess (返回参数说明) {float} start-price  价格
     * @apiSuccess (返回参数说明) {int} start-order  排序
     * @apiSuccess (返回参数说明) {Obj} wait 等待时间设置
     * @apiSuccess (返回参数说明) {int} wait-id 等待时间设置id
     * @apiSuccess (返回参数说明) {int} wait-free 免费等待时间
     * @apiSuccess (返回参数说明) {float} wait-price 超出免费等待时间价格：min/元
     * @apiSuccess (返回参数说明) {Obj} interval 起步价时间段设置
     * @apiSuccess (返回参数说明) {int} interval-id 起步价时间段设置id
     * @apiSuccess (返回参数说明) {int} interval-time_begin 起步价时间段开始时间
     * @apiSuccess (返回参数说明) {int} interval-time_end 起步价时间段结束时间
     * @apiSuccess (返回参数说明) {Float} interval-price 该时间段内的价格
     */
    public function initMINIPrice()
    {
        $params = $this->request->param();
        $info = (new SystemPriceService())->initMINIPrice($params);
        return json(new SuccessMessageWithData(['data' => $info]));
    }

    /**
     * @api {GET} /api/v1/SystemPrice/initIndex/mini  小程序用户端-获取首页初始化信息：价格/附近司机数量/优惠券
     * @apiGroup   MINI
     * @apiVersion 1.0.1
     * @apiDescription  小程序用户端-获取首页初始化信息：价格/附近司机数量/优惠券
     * @apiExample {get}  请求样例:
     * https://tonglingok.com/api/v1/SystemPrice/initIndex/mini?lat="11212.12121"&lng="3323.3223"
     * @apiParam (请求参数说明) {String} lat  纬度
     * @apiParam (请求参数说明) {String} lng  经度
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"tickets":[{"id":60,"name":"首次关注微信公众号优惠券","money":5,"time_begin":"2019-08-16 00:00:00","time_end":"2019-08-31 00:00:00"}],"drivers":0,"interval":[{"time_begin":"07:00","time_end":"20:59","price":10},{"time_begin":"21:00","time_end":"06:59","price":20}]}}
     * @apiSuccess (返回参数说明) {Obj} tickets 优惠券信息
     * @apiSuccess (返回参数说明) {int} tickets|id  优惠券id
     * @apiSuccess (返回参数说明) {String} tickets-name  优惠券名称
     * @apiSuccess (返回参数说明) {Float} tickets-money  优惠券金额
     * @apiSuccess (返回参数说明) {String} tickets-time_begin  有效期开始时间
     * @apiSuccess (返回参数说明) {String} tickets-time_end  有效期截止时间
     * @apiSuccess (返回参数说明) {int} drivers 附近候驾司机数量
     * @apiSuccess (返回参数说明) {Obj} interval 起步价时间段设置
     * @apiSuccess (返回参数说明) {int} interval-id 起步价时间段设置id
     * @apiSuccess (返回参数说明) {int} interval-time_begin 起步价时间段开始时间
     * @apiSuccess (返回参数说明) {int} interval-time_end 起步价时间段结束时间
     * @apiSuccess (返回参数说明) {Float} interval-price 该时间段内的价格
     */
    public function initMINIIndex()
    {
        $params = $this->request->param();
        $info = (new SystemPriceService())->loginInit($params);
        return json(new SuccessMessageWithData(['data' => $info]));
    }


    /**
     * @api {GET} /api/v1/SystemPrice/init/driver  Android司机端-获取初始化价格设置信息
     * @apiGroup   Android
     * @apiVersion 1.0.1
     * @apiDescription   Android司机端-获取初始化价格设置信息
     * @apiExample {get}  请求样例:
     * https://tonglingok.com/api/v1/SystemPrice/init/driver
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"start":[{"id":1,"distance":8,"price":18,"order":1},{"id":2,"distance":1,"price":80,"order":2},{"id":3,"distance":2,"price":15,"order":3}],"wait":{"id":1,"free":30,"price":1}}}
     * @apiSuccess (返回参数说明) {Obj} start  价格信息
     * @apiSuccess (返回参数说明) {int} start-id  价格设置id
     * @apiSuccess (返回参数说明) {float} start-distance  距离
     * @apiSuccess (返回参数说明) {float} start-price  价格
     * @apiSuccess (返回参数说明) {int} start-order  排序
     * @apiSuccess (返回参数说明) {Obj} wait 等待时间设置
     * @apiSuccess (返回参数说明) {int} wait-id 等待时间设置id
     * @apiSuccess (返回参数说明) {int} wait-free 免费等待时间
     * @apiSuccess (返回参数说明) {float} wait-price 超出免费等待时间价格：min/元
     */
    public function priceInfoForDriver()
    {
        $info = (new SystemPriceService())->priceInfoForDriver();
        return json(new SuccessMessageWithData(['data' => $info]));
    }

    /**
     * @api {GET} /api/v1/SystemPrice/order  CMS管理端-获取订单服务费设置信息
     * @apiGroup   CMS
     * @apiVersion 1.0.1
     * @apiDescription    CMS管理端-获取订单服务费设置信息
     * @apiExample {get}  请求样例:
     * https://tonglingok.com/api/v1/SystemPrice/order
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"id":1,"insurance":2.3,"order":0.2}}
     * @apiSuccess (返回参数说明) {int} id 设置id
     * @apiSuccess (返回参数说明) {Float} insurance 保险费用，固定金额
     * @apiSuccess (返回参数说明) {Float} order 订单抽成比例， 小于1
     */
    public function orderCharge()
    {
        $company_id = \app\api\service\Token::getCurrentTokenVar('company_id');
        $info = SystemOrderChargeT::where('company_id', $company_id)
            ->field('id,insurance,order')
            ->find();
        return json(new SuccessMessageWithData(['data' => $info]));
    }

    /**
     * @api {POST} /api/v1/SystemPrice/order/update CMS管理端-修改订单服务费设置信息
     * @apiGroup  CMS
     * @apiVersion 1.0.1
     * @apiDescription CMS管理端-修改订单服务费设置信息
     * @apiExample {post}  请求样例:
     *    {
     *       "id":1,
     *       "insurance":2.5
     *       "order":0.3
     *     }
     * @apiParam (请求参数说明) {Int} id  设置ID
     * @apiParam (请求参数说明) {Float} insurance 保险费用，固定金额
     * @apiParam (请求参数说明) {Float} order 订单抽成比例， 小于1
     * @apiSuccessExample {json} 返回样例:
     *{"msg":"ok","errorCode":0}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     */
    public function updateOrderCharge()
    {
        $info = $this->request->param();
        $res = (new SystemOrderChargeT())->isUpdate()->save($info);
        if (!$res) {
            throw  new UpdateException();
        }
        return json(new SuccessMessage());

    }

}