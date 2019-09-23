<?php
/**
 * Created by PhpStorm.
 * User: mingliang
 * Date: 2018/11/29
 * Time: 12:00 AM
 */


return [
    "default_options_name" => "ok",
    "mengant" => [
        'AccessKeyId' => '********', // 访问密钥，在阿里云的密钥管理页面创建
        'AccessSecret' => '************', // 访问密钥，在阿里云的密钥管理页面创建
        'TemplateCode' => 'SMS_******', // 短信模板ID
        'SignName' => '管理平台',
    ],
    "ok" => [
        'AccessKeyId' => 'LTAILmoLeYr2tPg9', // 访问密钥，在阿里云的密钥管理页面创建
        'AccessSecret' => 'IGyopSctAoahUva0wP4wYDOMBuz4aq', // 访问密钥，在阿里云的密钥管理页面创建
        'TemplateRegisterCode' => 'SMS_166310718', // 小程序注册
        'TemplateLoginCode' => 'SMS_166310720', // 司机端登录
        'TemplateDriverCode' => 'SMS_172884242', // 发送接单通知给司机端
        'TemplateRechargeCode' => 'SMS_171357306', // 充值通知给司机
        'TemplateMINICode' => 'SMS_173251945', // 发送接单通知给用户
        'TemplateDriverCreateOrderCode' => 'SMS_173251947', // 司机自主建单通知
        'TemplateOrderCompleteCode' => 'SMS_173251946', // 结算通知
        'TemplateTicketCode' => 'SMS_174655424', // 派送优惠券通知
        'SignName' => 'OK代驾'
    ],
];