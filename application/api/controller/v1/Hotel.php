<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\model\HotelT;
use app\api\model\OrderV;
use app\api\service\HotelService;
use app\lib\enum\CommonEnum;
use app\lib\exception\ParameterException;
use app\lib\exception\SaveException;
use app\lib\exception\SuccessMessage;
use app\lib\exception\SuccessMessageWithData;
use app\lib\exception\UpdateException;
use think\facade\Request;
use app\api\service\Token as TokenService;

class Hotel extends BaseController
{

    /**
     * @api {POST} /api/v1/hotel/save CMS管理端-新增酒店
     * @apiGroup   CMS
     * @apiVersion 1.0.1
     * @apiDescription  CMS管理端-新增酒店
     * @apiExample {post}  请求样例:
     *    {
     *       "name": "大红灯笼",
     *       "address": "欣明国际旁",
     *       "username": "韩先生",
     *       "phone": "18956225230",
     *       "lat": "111",
     *       "lng": "111"
     *     }
     * @apiParam (请求参数说明) {String} name  酒店名称
     * @apiParam (请求参数说明) {String} address  酒店地址
     * @apiParam (请求参数说明) {String} username  联系人
     * @apiParam (请求参数说明) {String} phone  手机号
     * @apiParam (请求参数说明) {String} lat  纬度
     * @apiParam (请求参数说明) {String} lng  经度
     * @apiSuccessExample {json} 返回样例:
     *{"msg":"ok","errorCode":0}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     */
    public function save()
    {
        $params = Request::param();
        $params['company_id'] = TokenService::getCurrentTokenVar('company_id');
        $hotel = HotelT::create($params);
        if (!$hotel) {
            throw  new SaveException();
        }
        return json(new SuccessMessage());
    }

    /**
     * @api {POST} /api/v1/hotel/update CMS管理端-修改酒店
     * @apiGroup   CMS
     * @apiVersion 1.0.1
     * @apiDescription  CMS管理端-修改酒店
     * @apiExample {post}  请求样例:
     *    {
     *       "id": 1,
     *       "name": "大红灯笼",
     *       "name": "大红灯笼",
     *       "address": "欣明国际旁",
     *       "username": "韩先生",
     *       "phone": "18956225230",
     *       "lat": "111",
     *       "lng": "111"
     *     }
     * @apiParam (请求参数说明) {String} id  酒店id
     * @apiParam (请求参数说明) {String} name  酒店名称
     * @apiParam (请求参数说明) {String} address  酒店地址
     * @apiParam (请求参数说明) {String} username  联系人
     * @apiParam (请求参数说明) {String} phone  手机号
     * @apiParam (请求参数说明) {String} lat  纬度
     * @apiParam (请求参数说明) {String} lng  经度
     * @apiSuccessExample {json} 返回样例:
     *{"msg":"ok","errorCode":0}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     */
    public function update()
    {
        $params = Request::param();
        $hotel = HotelT::update($params);
        if (!$hotel) {
            throw  new UpdateException();
        }
        return json(new SuccessMessage());
    }

    /**
     * @api {POST} /api/v1/hotel/handel CMS管理端-停用酒店账号
     * @apiGroup   CMS
     * @apiVersion 1.0.1
     * @apiDescription  CMS管理端-停用酒店账号
     * @apiSuccessExample {json} 返回样例:
     * {"msg": "ok","errorCode": 0}
     * @apiSuccess (返回参数说明) {int} errorCode 错误代码 0 表示没有错误
     * @apiSuccess (返回参数说明) {String} msg 操作结果描述
     *
     */
    public function handel()
    {
        $params = Request::param();
        $params['state'] = CommonEnum::STATE_IS_FAIL;
        $hotel = HotelT::update($params);
        if (!$hotel) {
            throw  new UpdateException();
        }
        return json(new SuccessMessage());
    }

    /**
     * @api {GET} /api/v1/hotels CMS管理端-获取酒店列表
     * @apiGroup  CMS
     * @apiVersion 1.0.1
     * @apiDescription CMS管理端-获取酒店列表
     * @apiExample {get}  请求样例:
     * https://tonglingok.com/api/v1/hotels?page=1&size=10
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":1,"per_page":20,"current_page":1,"last_page":1,"data":[{"id":1,"name":"大红灯笼","lat":"111","lng":"111","create_time":"2019-10-23 17:01:00","company_id":3,"username":"张小姐","phone":"18956225230","qrcode":"http://"}]}}
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} id 酒店id
     * @apiSuccess (返回参数说明) {String} name  酒店名称
     * @apiSuccess (返回参数说明) {String} address  酒店地址
     * @apiSuccess (返回参数说明) {String} username  联系人
     * @apiSuccess (返回参数说明) {String} phone  手机号
     * @apiSuccess (返回参数说明) {String} lat  纬度
     * @apiSuccess (返回参数说明) {String} lng  经度
     * @apiSuccess (返回参数说明) {String} qrcode  商家小程序二维码地址
     * @apiSuccess (返回参数说明) {String} create_time 创建时间
     */
    public function hotels($page = 1, $size = 20)
    {
        $company_id = TokenService::getCurrentTokenVar('company_id');
        $hotels = HotelT::hotels($company_id, $page, $size);
        return json(new SuccessMessageWithData(['data' => $hotels]));
    }

    /**
     * @api {GET} /api/v1/hotel/orders CMS管理端--获取酒店订单列表
     * @apiGroup  CMS
     * @apiVersion 1.0.1
     * @apiDescription   CMS管理端-获取酒店订单列表
     * @apiExample {get}  请求样例:
     * https://tonglingok.com/api/v1/hotel/orders?time_begin=2019-09-01&time_end=2019-10-28&hotel_id=1&page=1&szie=20
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {int} hotel_id 酒店id
     * @apiParam (请求参数说明) {string} time_begin 查询开始时间
     * @apiParam (请求参数说明) {string} time_end 查询结束时间
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":1,"per_page":20,"current_page":1,"last_page":1,"data":[{"id":1791,"name":"先生\/女士","driver":"朱明良","phone":"18956225230","money":"18.00","start":"在美居建材家具广场附近","end":"在美居建材家具广场附近","hotel":"大红灯笼","state":4,"create_time":"2019-10-23 14:14:48"}]}}
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} id 订单id
     * @apiSuccess (返回参数说明) {String} start 出发点
     * @apiSuccess (返回参数说明) {String} end 目的地
     * @apiSuccess (返回参数说明) {String} name 客户姓名
     * @apiSuccess (返回参数说明) {String} driver 司机姓名
     * @apiSuccess (返回参数说明) {String} phone 客户手机号
     * @apiSuccess (返回参数说明) {float} money 订单金额
     * @apiSuccess (返回参数说明) {String}  create_time 创建时间
     * @apiSuccess (返回参数说明) {int} state 订单状态：1 | 未接单；2 | 已接单；4 | 完成；
     */
    public function orders($page = 1, $size = 20)
    {
        $time_begin = Request::param('time_begin');
        $time_end = Request::param('time_end');
        $hotel_id = Request::param('hotel_id');
        $orders = OrderV::hotelOrders($hotel_id, $time_begin, $time_end, $page, $size);
        return json(new SuccessMessageWithData(['data' => $orders]));

    }


    /**
     * @api {POST} /api/v1/hotel/qrcode/create CMS管理端-创建酒店二维码
     * @apiGroup   CMS
     * @apiVersion 1.0.1
     * @apiDescription  CMS管理端-创建酒店二维码
     * @apiExample {post}  请求样例:
     *    {
     *       "hotel_id": 1
     *     }
     * @apiParam (请求参数说明) {String} hotel_id  酒店id
     * @apiSuccessExample {json} 返回样例:
     *{"msg":"ok","errorCode":0,"data":{"url":"http://"}}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     * @apiSuccess (返回参数说明) {String} url 二维码地址
     */
    public function createQRCode()
    {
        $hotel_id = Request::param('hotel_id');
        $url = (new HotelService())->createQRCode($hotel_id);
        return json(new SuccessMessageWithData(["data" => ['url' => $url]]));

    }

    /**
     * @api {POST} /api/v1/hotel/qrcode/download CMS管理端-下载酒店二维码
     * @apiGroup   CMS
     * @apiVersion 1.0.1
     * @apiDescription  CMS管理端-下载酒店二维码
     * @apiExample {post}  请求样例:
     *    {
     *       "hotel_id": 1
     *     }
     * @apiParam (请求参数说明) {String} hotel_id  酒店id
     */
    public function downLoadQRCode()
    {
        $hotel_id = Request::param('hotel_id');
        $hotel = HotelT::where('id', $hotel_id)->find();
        if (empty($hotel['qrcode'])) {
            throw new ParameterException(['msg' => '二维码未生成']);
        }
        $QRCode = dirname($_SERVER['SCRIPT_FILENAME']) . $hotel['qrcode'];
        $download = new \think\response\Download($QRCode);
        return $download->name($hotel_id . '.png');
    }

}