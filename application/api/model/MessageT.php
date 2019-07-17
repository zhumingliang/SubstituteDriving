<?php


namespace app\api\model;


use think\Model;

class MessageT extends Model
{
    public function user()
    {
        return $this->belongsTo('UserT', 'u_id', 'id');

    }

    public static function messages($page, $size)
    {

        $list = self::with(['user' => function ($query) {
            $query->field('id,nickName,phone');
        }])
            ->order('create_time desc')
            ->paginate($size, false, ['page' => $page]);
        return $list;
    }
}