<?php


namespace app\api\model;


use app\lib\enum\CommonEnum;
use think\Model;

class TicketV extends Model
{
    public static function ticketsForCMS($page, $size, $time_begin, $time_end, $key)
    {
        $time_end = addDay(1, $time_end);
        $list = self::where('state','<',CommonEnum::STATE_IS_DELETE)
        ->where(function ($query) use ($key) {
            if (strlen($key)) {
                $query->where('name', 'like', '%' . $key . '%');
            }
        })
            ->where(function ($query) use ($time_begin, $time_end) {
                if (strlen($time_begin) && strlen($time_end)) {
                    $query->whereBetweenTime('create_time', $time_begin, $time_end);

                }
            })
            ->hidden(['u_id','scene','source'])
            ->paginate($size, false, ['page' => $page]);
        return $list;
    }

}