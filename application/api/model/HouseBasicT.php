<?php


namespace app\api\model;


use app\api\service\HouseService;
use app\lib\enum\CommonEnum;
use think\Model;

class HouseBasicT extends Model
{
    public function images()
    {
        return $this->hasMany('HouseImageT', 'house_id', 'id');
    }

    public static function houses($category_id, $city_id, $page, $size)
    {
        $houses = self::where('city_id', $city_id)
            ->where('category_id', $category_id)
            ->where('state', CommonEnum::STATE_IS_OK)
            ->with([
                'images' => function ($query) {
                    $query->where('state', CommonEnum::STATE_IS_OK)
                        ->field('id,house_id,url')
                        ->limit(0, 1);
                }
            ])
            ->field('id,name')
            ->order('id desc')
            ->paginate($size, false, ['page' => $page])->toArray();
        return $houses;
    }

    public static function house($house_id)
    {
        return self::where('id', $house_id)
            ->with([
                'images' => function ($query) {
                    $query->where('state', CommonEnum::STATE_IS_OK)
                        ->field('id,house_id,url');
                }
            ])->find();
    }

}