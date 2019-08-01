<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\model\NoticeT;
use app\api\service\NoticeService;
use app\lib\enum\CommonEnum;
use app\lib\exception\SaveException;
use app\lib\exception\SuccessMessage;
use app\lib\exception\SuccessMessageWithData;
use app\lib\exception\UpdateException;

class Notice extends BaseController
{
    /**
     * @api {POST} /api/v1/notice/save  Android管理端/PC管理端-新增系统公告
     * @apiGroup  COMMON
     * @apiVersion 1.0.1
     * @apiDescription  Android管理端-新增系统公告
     * @apiExample {post}  请求样例:
     *    {
     *       "area":"铜陵",
     *       "title":"我是标题",
     *       "content": "我是内容"
     *       "from": "android"
     *     }
     * @apiParam (请求参数说明) {String} area  公告发区域 android管理端无需上传
     * @apiParam (请求参数说明) {String} title  标题
     * @apiParam (请求参数说明) {String} content  内容
     * @apiParam (请求参数说明) {String} from  发布来源：android/pc
     * @apiSuccessExample {json} 返回样例:
     *{"msg":"ok","errorCode":0}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     */
    public function save()
    {
        $params = $this->request->param();
        $params['state'] = CommonEnum::STATE_IS_OK;
        $params['area'] = '铜陵';
        $params['type'] = 1;
        $params['admin_id'] = \app\api\service\Token::getCurrentUid();
        $start = NoticeT::create($params);
        if (!$start) {
            throw new SaveException();
        }
        return json(new SuccessMessage());
    }

    /**
     * @api {POST} /api/v1/notice/handel Android管理端/PC管理端-修改系统公告状态
     * @apiGroup  COMMON
     * @apiVersion 1.0.1
     * @apiDescription  Android管理端/PC管理端-修改系统公告状态
     * @apiExample {POST}  请求样例:
     * {
     * "id": 1,
     * "state":2
     * }
     * @apiParam (请求参数说明) {int} id 公告id
     * @apiParam (请求参数说明) {int} state 状态：1 | 未发布；2 | 发布；3|删除
     * @apiSuccessExample {json} 返回样例:
     * {"msg": "ok","errorCode": 0}
     * @apiSuccess (返回参数说明) {int} errorCode 错误代码 0 表示没有错误
     * @apiSuccess (返回参数说明) {String} msg 操作结果描述
     *
     */
    public function handel()
    {
        $params = $this->request->param();
        $id = NoticeT::update(['state' => $params['state']], ['id' => $params['id']]);
        if (!$id) {
            throw new UpdateException();
        }
        return json(new SuccessMessage());

    }

    /**
     * @api {POST} /api/v1/notice/update Android管理端/PC管理端-修改系统公告内容
     * @apiGroup  COMMON
     * @apiVersion 1.0.1
     * @apiDescription Android管理端/PC管理端-修改系统公告内容
     * @apiExample {post}  请求样例:
     *    {
     *       "id":1,
     *       "title":"修改标题",
     *       "content":"修改内容",
     *     }
     * @apiParam (请求参数说明) {int} id    明细ID
     * @apiParam (请求参数说明) {String} title  标题
     * @apiParam (请求参数说明) {String} content  内容
     *
     * @apiSuccessExample {json} 返回样例:
     *{"msg":"ok","errorCode":0}
     * @apiSuccess (返回参数说明) {int} errorCode 错误码： 0表示操作成功无错误
     * @apiSuccess (返回参数说明) {String} msg 信息描述
     */
    public function update()
    {
        $info = $this->request->param();
        (new NoticeT())->isUpdate()->save($info);
        return json(new SuccessMessage());

    }

    /**
     * @api {GET} /api/v1/notices/android Android管理端/Android司机端-获取通知列表
     * @apiGroup  Android
     * @apiVersion 1.0.1
     * @apiDescription
     * @apiExample {get}  请求样例:
     * https://tonglingok.com/api/v1/notices/android?page=1&size=10
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":3,"per_page":10,"current_page":1,"last_page":1,"data":[{"id":1,"title":"123","content":"1212121","state":"未发布"},{"id":2,"title":"123sadas","content":"1212121","state":"未发布"},{"id":3,"title":"123sadasasdadsa","content":"sdadada","state":"未发布"}]}}
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} id 通知id
     * @apiSuccess (返回参数说明) {String} title 标题
     * @apiSuccess (返回参数说明) {String} content  内容
     * @apiSuccess (返回参数说明) {String} create_time  创建时间
     * @apiSuccess (返回参数说明) {String} state 状态：管理端返回该字段
     */
    public function AndroidNotices($page = 1, $size = 10)
    {
        $notices = (new NoticeService())->AndroidNotices($page, $size);
        return json(new SuccessMessageWithData(['data' => $notices]));

    }

    /**
     * @api {GET} /api/v1/notices/cms CMS管理端-获取通知公告列表
     * @apiGroup  CMS
     * @apiVersion 1.0.1
     * @apiDescription
     * @apiExample {get}  请求样例:
     * https://tonglingok.com/api/v1/notices/cms?key=2&time_begin=2019-06-28&time_end=2019-06-29&page=1&size=10&type=1&area="铜陵"
     * @apiParam (请求参数说明) {int} page 当前页码
     * @apiParam (请求参数说明) {int} size 每页多少条数据
     * @apiParam (请求参数说明) {String} key 关键字查询： CMS端传入该字段，Android端无需传入
     * @apiParam (请求参数说明) {String} time_begin 查询开始时间：CMS端传入该字段，Android端无需传入
     * @apiParam (请求参数说明) {String} time_end 查询开始时间：CMS端传入该字段，Android端无需传入
     * @apiParam (请求参数说明) {int} type 公告类型，默认传入1
     * @apiParam (请求参数说明) {String} area 地区，默认传入铜陵
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"total":2,"per_page":10,"current_page":1,"last_page":1,"data":[{"id":2,"type":1,"title":"123sadas","content":"1212121","area":"铜陵","admin_id":1,"state":"未发布","create_time":"2019-07-04 11:16:20","update_time":"2019-07-04 11:16:20","admin":{"id":1,"username":"管理员"}},{"id":3,"type":1,"title":"123sadasasdadsa","content":"sdadada","area":"铜陵","admin_id":1,"state":"未发布","create_time":"2019-07-04 11:16:24","update_time":"2019-07-04 11:16:24","admin":{"id":1,"username":"管理员"}}]}}
     * @apiSuccess (返回参数说明) {int} total 数据总数
     * @apiSuccess (返回参数说明) {int} per_page 每页多少条数据
     * @apiSuccess (返回参数说明) {int} current_page 当前页码
     * @apiSuccess (返回参数说明) {int} last_page 最后页码
     * @apiSuccess (返回参数说明) {int} id 通知id
     * @apiSuccess (返回参数说明) {String} title 标题
     * @apiSuccess (返回参数说明) {String} content  内容
     * @apiSuccess (返回参数说明) {String} state 状态：管理端返回该字段
     * @apiSuccess (返回参数说明) {String} create_time 创建时间
     * @apiSuccess (返回参数说明) {String} username 创建人
     * @apiSuccess (返回参数说明) {Obj} admin 创建人
     * @apiSuccess (返回参数说明) {String} admin-username 创建人用户名
     */
    public function CMSNotices($page = 1, $size = 10, $time_begin = '', $time_end = '', $type = 1, $area = "铜陵", $key = '')
    {
        $notices = (new NoticeService())->CMSNotices($page, $size, $time_begin, $time_end, $type, $area, $key);
        return json(new SuccessMessageWithData(['data' => $notices]));
    }


    /**
     * @api {GET} /api/v1/notice CMS管理端/Android管理端/Android司机端-获取通知公详情
     * @apiGroup  CMS
     * @apiVersion 1.0.1
     * @apiDescription
     * @apiExample {get}  请求样例:
     * https://tonglingok.com/api/v1/notice?id=1
     * @apiParam (请求参数说明) {int} id 通知公告id
     * @apiSuccessExample {json} 返回样例:
     * {"msg":"ok","errorCode":0,"code":200,"data":{"id":1,"title":"1234","content":"测试修改","create_time":"2019-07-04 11:16:16"}}
     * @apiSuccess (返回参数说明) {int} id 通知id
     * @apiSuccess (返回参数说明) {String} title 标题
     * @apiSuccess (返回参数说明) {String} content  内容
     * @apiSuccess (返回参数说明) {String} create_time 创建时间
     */
    public function notice()
    {
        $id = $this->request->param('id');
        $info = NoticeT::where('id', $id)->field('id,title,content,create_time')->find();
        return json(new SuccessMessageWithData(['data' => $info]));
    }


}