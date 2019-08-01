<?php


namespace app\api\service;


use app\api\model\NoticeT;

class NoticeService
{

    public function AndroidNotices($page, $size)
    {
        $user_grade = Token::getCurrentTokenVar('type');
        $notices = array();
        if ($user_grade == 'driver') {
            $notices = NoticeT::noticesForDriver($page, $size);

        } else if ($user_grade == 'manager') {
            $notices = NoticeT::noticesForManager($page, $size);
        }
        return $notices;

    }


    public function CMSNotices($page, $size, $time_begin, $time_end, $type, $area, $key)
    {
        $notices = NoticeT::CMSNotices($page, $size, $time_begin, $time_end, $type, $area, $key);

        $data = $notices['data'];
        if (count($data)) {
            foreach ($data as $k => $v) {
                $data[$k]['state'] = $v['state'] == 1 ? "未发布" : "已发布";
            }
        }
        $notices['data'] = $data;
        return $notices;
    }

}