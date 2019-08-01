<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class NoticeT extends Model
{

    public function admin()
    {
        return $this->belongsTo('AdminT', 'admin_id', 'id');
    }

    public static function noticesForManager($page, $size)
    {
        $list = self::whereIn('state', ['1,2'])
            ->field('id,title,content,state')
            ->paginate($size, false, ['page' => $page]);
        return $list;

    }

    public static function noticesForDriver($page, $size)
    {
        $list = self::where('state', CommonEnum::NOTICE_RELEASED)
            ->field('id,title,content')
            ->paginate($size, false, ['page' => $page]);
        return $list;

    }


    public static function CMSNotices($page, $size, $time_begin, $time_end, $type, $area, $key)
    {
        $list = self::with(['admin' => function ($query) use ($key) {
            $query->field('id,username');
        }])
            ->where('state', '<', '3')
            ->where(function ($query) use ($key) {
                if (strlen($key)) {
                    $query->where('title', 'like', '%' . $key . '%');
                }
            })
            ->where(function ($query) use ($time_begin, $time_end) {
                if (strlen($time_begin) && strlen($time_end)) {
                    $query->whereBetweenTime('create_time', $time_begin, $time_end);

                }
            })
            ->hidden(['u_id', 'scene', 'source'])
            ->paginate($size, false, ['page' => $page]);
        return $list;

    }
}