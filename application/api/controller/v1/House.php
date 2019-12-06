<?php


namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\HouseService;
use app\lib\exception\SuccessMessage;
use app\lib\exception\SuccessMessageWithData;
use think\facade\Request;

class House extends BaseController
{
    public function save()
    {
        $params = Request::param();
        (new HouseService())->save($params);
        return json(new SuccessMessage());
    }

    public function cities()
    {
        $cities = (new HouseService())->cities();
        return json(new SuccessMessageWithData(['data' => $cities]));
    }

    public function categories()
    {
        $categories = (new HouseService())->categories();
        return json(new SuccessMessageWithData(['data' => $categories]));
    }

    public function houses( $city_id, $page = 1, $size = 10,$category_id=0)
    {
        $houses = (new HouseService())->houses($category_id, $city_id, $page, $size);
        return json(new SuccessMessageWithData(['data' => $houses]));
    }

    public function house($house_id)
    {
        $house = (new HouseService())->house($house_id);
        return json(new SuccessMessageWithData(['data' => $house]));

    }

    public function apply()
    {
        $params = Request::param();
        (new HouseService())->apply($params);
        return json(new SuccessMessage());

    }

}