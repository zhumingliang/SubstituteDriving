<?php
/**
 * Created by PhpStorm.
 * User: mingliang
 * Date: 2018/9/18
 * Time: 下午11:25
 */

namespace app\lib\enum;


class CommonEnum
{


    const STATE_IS_OK = 1;

    const STATE_IS_FAIL = 2;

    const READY = 1;

    const PASS = 2;

    const REFUSE = 3;

    const DELETE = 3;

    const ORDER_STATE_INIT = 99999;

    const ORDER_IS_BOOKING = 1;

    const ORDER_IS_DEMAND = 2;

    const ORDER_IS_BOND = 3;

    const ORDER_IS_SCORE = 4;

    const EXTEND_HOUSE = 1;

    const EXTEND_REPAIR = 2;

    const WITHDRAW_BOND = 1;

    const WITHDRAW_BUSINESS = 2;

    const RECIPIENT_SHOP = 2;

    const RECIPIENT_NORMAL = 1;

}