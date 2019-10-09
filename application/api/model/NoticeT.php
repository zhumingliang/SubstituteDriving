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

    public static function noticesForManager($company_id, $page, $size)
    {
        $list = self::where('company_id', $company_id)
            ->where('state', '<', 3)
            ->field('id,title,content,state,create_time')
            ->order('create_time desc')
            ->paginate($size, false, ['page' => $page]);
        return $list;

    }

    public static function noticesForDriver($company_id, $page, $size)
    {
        $list = self::where('company_id', $company_id)
            ->where('state', CommonEnum::NOTICE_RELEASED)
            ->field('id,title,content,create_time')
            ->order('create_time desc')
            ->paginate($size, false, ['page' => $page]);
        return $list;

    }


    public static function CMSNotices($company_id, $page, $size, $time_begin, $time_end, $type, $area, $key)
    {
        $list = self::where('company_id', $company_id)
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
            })->with(['admin' => function ($query) use ($key) {
                $query->field('id,username');
            }])
            ->hidden(['u_id', 'scene', 'source'])
            ->order('create_time desc')
            ->paginate($size, false, ['page' => $page])
            ->toArray();
        return $list;

    }
}