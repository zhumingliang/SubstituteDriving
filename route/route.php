<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

Route::rule('api/:version/index', 'api/:version.Index/index');
Route::rule('api/:version/send', 'api/:version.Index/send');

Route::post('api/:version/token/admin', 'api/:version.Token/getAdminToken');
Route::post('api/:version/token/android', 'api/:version.Token/getAndroidToken');
Route::post('api/:version/token/small', 'api/:version.Token/getSmallToken');
Route::get('api/:version/token/login/out', 'api/:version.Token/loginOut');

Route::post('api/:version/user/info', 'api/:version.User/userInfo');
