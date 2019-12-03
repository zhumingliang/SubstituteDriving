<?php


namespace app\api\service;


use app\api\model\CategoryT;
use app\api\model\CityT;
use app\api\model\HouseBasicT;
use app\api\model\HouseImageT;
use app\lib\enum\CommonEnum;
use app\lib\exception\SaveException;
use app\lib\exception\SuccessMessageWithData;

class HouseService
{
    public function save($params)
    {
        /* $house = HouseBasicT::create($params);
         if (!$house) {
             throw new SaveException();
         }
         if (!empty($params['images'])) {
             $images = explode(',', $params['images']);
             $data = [];
             foreach ($images as $k => $v) {
                 array_push($data, [
                     'state' => CommonEnum::STATE_IS_OK,
                     'url' => '/static/image/' . $v . '.jpg',
                     'house_id' => $house->id
                 ]);
             }
             (new HouseImageT())->saveAll($data);
         }*/
        if (!empty($params['images'])) {
            $images = explode(',', $params['images']);
            $data = [];
            foreach ($images as $k => $v) {
                array_push($data, [
                    'state' => CommonEnum::STATE_IS_OK,
                    'url' => '/static/image/' .'14-'.$v . '.jpg',
                    'house_id' => 14
                ]);
            }
            (new HouseImageT())->saveAll($data);
        }

    }

    public function cities()
    {
        $city = CityT::where('state', CommonEnum::STATE_IS_OK)
            ->field('id,name')
            ->select();
        return $city;
    }

    public function categories()
    {
        $categories = CategoryT::where('state', CommonEnum::STATE_IS_OK)
            ->field('id,name')
            ->select();
        return $categories;
    }

    public function houses($category_id, $city_id, $page, $size)
    {
        $houses = HouseBasicT::houses($category_id, $city_id, $page, $size);
        return $houses;
    }

    public function house($house_id)
    {
        return HouseBasicT::house($house_id);
    }

}