<?php


namespace app\api\service;


class BaseService
{
    public static function getLocationCacheKey($company_id)
    {
        $company_id = empty($company_id) ? 1 : $company_id;
        $save_location_key = "driver:location:$company_id";
        return $save_location_key;
    }

}