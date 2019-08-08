<?php
/**
 * Created by 七月.
 * Author: 七月
 * Date: 2017/5/26
 * Time: 12:50
 */

namespace app\lib\enum;


class OrderEnum
{
    const FROM_MINI = 1;

    const FROM_DRIVER = 2;

    const FROM_MANAGER = 3;

    const FROM_PUBLIC = 4;

    const NOT_FIXED_MONEY = 1;

    const FIXED_MONEY = 2;

    const ORDER_NO = 1;

    const ORDER_ING = 2;

    const ORDER_TRANSFER = 3;

    const ORDER_COMPLETE = 4;

    const ORDER_CANCEL = 5;

    const ORDER_STOP = 1;


    const ORDER_LIST_NO = 1;

    const ORDER_LIST_ING = 2;

    const ORDER_LIST_COMPLETE = 3;

    const ORDER_LIST_CANCEL = 4;

    const ORDER_PUSH_NO = 1;

    const ORDER_PUSH_AGREE = 2;

    const ORDER_PUSH_REFUSE = 3;

    const ORDER_PUSH_INVALID = 4;

    const ORDER_PUSH_WITHDRAW = 5;


}