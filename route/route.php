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
Route::get('api/:version/token/user', 'api/:version.Token/getUserToken');
Route::get('api/:version/token/public/user', 'api/:version.Token/getUserPublicToken');
Route::post('api/:version/token/android', 'api/:version.Token/getAndroidToken');
Route::post('api/:version/token/small', 'api/:version.Token/getSmallToken');
Route::get('api/:version/token/login/out', 'api/:version.Token/loginOut');

Route::post('api/:version/user/info', 'api/:version.User/userInfo');
Route::post('api/:version/user/public/info', 'api/:version.User/userPublicInfo');
Route::post('api/:version/user/check/bind', 'api/:version.User/checkBind');
Route::post('api/:version/user/bindPhone', 'api/:version.User/bindPhone');
Route::get('api/:version/user/login/out', 'api/:version.User/loginOut');
Route::get('api/:version/users', 'api/:version.User/users');

Route::post('api/:version/gateway/bind', 'api/:version.Gateway/bind');

Route::post('api/:version/driver/save', 'api/:version.Driver/save');
Route::post('api/:version/driver/update', 'api/:version.Driver/update');
Route::post('api/:version/driver/send', 'api/:version.Driver/send');
Route::get('api/:version/drivers', 'api/:version.Driver/drivers');
Route::get('api/:version/drivers/nearby', 'api/:version.Driver/nearbyDrivers');
Route::get('api/:version/driver/order/check', 'api/:version.Driver/checkDriverHasUnCompleteOrder');
Route::post('api/:version/driver/handel', 'api/:version.Driver/handel');
Route::post('api/:version/driver/online', 'api/:version.Driver/online');
Route::get('api/:version/driver/online/records', 'api/:version.Driver/onlineRecords');
Route::get('api/:version/driver/acceptableOrder', 'api/:version.Driver/acceptableOrder');
Route::get('api/:version/driver/income', 'api/:version.Driver/income');

Route::post('api/:version/ticket/save', 'api/:version.Ticket/save');
Route::post('api/:version/ticket/update', 'api/:version.Ticket/update');
Route::post('api/:version/ticket/handel', 'api/:version.Ticket/handel');
Route::post('api/:version/ticket/send', 'api/:version.Ticket/send');
Route::get('api/:version/tickets/manage', 'api/:version.Ticket/ManageTickets');
Route::get('api/:version/ticket', 'api/:version.Ticket/ticket');
Route::get('api/:version/tickets/user', 'api/:version.Ticket/userTickets');

Route::post('api/:version/SystemPrice/interval/save', 'api/:version.SystemPrice/intervalSave');
Route::post('api/:version/SystemPrice/interval/handel', 'api/:version.SystemPrice/intervalHandel');
Route::post('api/:version/SystemPrice/interval/update', 'api/:version.SystemPrice/intervalUpdate');
Route::get('api/:version/SystemPrice/interval', 'api/:version.SystemPrice/intervalPrice');

Route::post('api/:version/SystemPrice/start/save', 'api/:version.SystemPrice/startSave');
Route::post('api/:version/SystemPrice/start/handel', 'api/:version.SystemPrice/startHandel');
Route::post('api/:version/SystemPrice/start/open/handel', 'api/:version.SystemPrice/startOpenHandel');
Route::get('api/:version/SystemPrice/start', 'api/:version.SystemPrice/startPrice');
Route::post('api/:version/SystemPrice/start/update', 'api/:version.SystemPrice/startUpdate');

Route::get('api/:version/SystemPrice/wait', 'api/:version.SystemPrice/waitPrice');
Route::post('api/:version/SystemPrice/wait/update', 'api/:version.SystemPrice/waitUpdate');

Route::get('api/:version/SystemPrice/order', 'api/:version.SystemPrice/orderCharge');
Route::post('api/:version/SystemPrice/order/update', 'api/:version.SystemPrice/updateOrderCharge');


Route::get('api/:version/SystemPrice/weather', 'api/:version.SystemPrice/weatherPrice');
Route::post('api/:version/SystemPrice/weather/update', 'api/:version.SystemPrice/weatherUpdate');

Route::get('api/:version/SystemPrice/init/mini', 'api/:version.SystemPrice/priceInfoForMINI');
Route::get('api/:version/SystemPrice/init/driver', 'api/:version.SystemPrice/priceInfoForDriver');

Route::post('api/:version/notice/save', 'api/:version.Notice/save');
Route::post('api/:version/notice/update', 'api/:version.Notice/update');
Route::post('api/:version/notice/handel', 'api/:version.Notice/handel');
Route::get('api/:version/notices/android', 'api/:version.Notice/AndroidNotices');
Route::get('api/:version/notices/cms', 'api/:version.Notice/CMSNotices');
Route::get('api/:version/notice', 'api/:version.Notice/notice');

Route::post('api/:version/recharge/save', 'api/:version.Wallet/saveRecharge');
Route::get('api/:version/recharges', 'api/:version.Wallet/recharges');
Route::get('api/:version/recharges/driver', 'api/:version.Wallet/driverRecharges');
Route::get('api/:version/wallet/records/driver', 'api/:version.Wallet/driverRecords');
Route::get('api/:version/wallet/records/manager', 'api/:version.Wallet/managerRecords');


Route::post('api/:version/sms/register', 'api/:version.SendSMS/sendCodeToMINI');
Route::post('api/:version/sms/login', 'api/:version.SendSMS/sendCodeToAndroid');

Route::post('api/:version/order/mini/save', 'api/:version.Order/saveMiniOrder');
Route::post('api/:version/order/drive/save', 'api/:version.Order/saveDriverOrder');
Route::post('api/:version/order/manager/save', 'api/:version.Order/saveManagerOrder');
Route::post('api/:version/order/push/handel', 'api/:version.Order/orderPushHandel');
Route::post('api/:version/order/cancel', 'api/:version.Order/orderCancel');
Route::post('api/:version/order/withdraw', 'api/:version.Order/withdraw');
Route::post('api/:version/order/begin', 'api/:version.Order/orderBegin');
Route::post('api/:version/order/begin/wait', 'api/:version.Order/beginWait');
Route::post('api/:version/order/arriving', 'api/:version.Order/orderArriving');
Route::get('api/:version/orders/mini', 'api/:version.Order/miniOrders');
Route::get('api/:version/orders/driver', 'api/:version.Order/driverOrders');
Route::get('api/:version/orders/today', 'api/:version.Order/todayOrders');
Route::get('api/:version/orders/manager', 'api/:version.Order/managerOrders');
Route::get('api/:version/orders/manager/cms', 'api/:version.Order/CMSManagerOrders');
Route::get('api/:version/orders/insurance/cms', 'api/:version.Order/CMSInsuranceOrders');
Route::get('api/:version/orders/current', 'api/:version.Order/current');
Route::get('api/:version/order/info', 'api/:version.Order/orderInfo');
Route::get('api/:version/order/mini', 'api/:version.Order/miniOrder');
Route::get('api/:version/order/end', 'api/:version.Order/driverOrderWithEnd');
Route::get('api/:version/order/consumption/records', 'api/:version.Order/recordsOfConsumption');
Route::get('api/:version/order/locations', 'api/:version.Order/orderLocations');
Route::post('api/:version/order/driver/complete', 'api/:version.Order/orderComplete');
Route::post('api/:version/order/transfer', 'api/:version.Order/transferOrder');
Route::post('api/:version/order/transferOrder/manager', 'api/:version.Order/choiceDriverByManager');


Route::get('api/:version/messages', 'api/:version.Message/messages');
Route::post('api/:version/message/save', 'api/:version.Message/save');


Route::rule('api/:version/order/list/handel', 'api/:version.Order/orderListHandel');
Route::rule('api/:version/order/push/no/handel', 'api/:version.Order/handelDriverNoAnswer');

Route::rule('api/:version/weixin/public', 'api/:version.WeiXinPublic/server');
Route::rule('api/:version/weixin/public/menu', 'api/:version.WeiXinPublic/createMenu');

