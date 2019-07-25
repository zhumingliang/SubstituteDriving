<?php

return [
    'app_id' => 'wxff0de9d71076ff70',

    'app_secret' => '1135eb5f46fa71637565fa46ad1c354b',

    'login_url' => "https://api.weixin.qq.com/sns/jscode2session?" .
        "appid=%s&secret=%s&js_code=%s&grant_type=authorization_code",

    'access_token_url' => "https://api.weixin.qq.com/cgi-bin/token?" .
        "grant_type=client_credential&appid=%s&secret=%s",

    'qrcode_page' => 'page/index/index?id=%s&grade=%s&client_id=%s',

    'qrcode_url' => 'https://api.weixin.qq.com/wxa/getwxacode?access_token=%s',


];