<?php


namespace app\api\service;


use app\api\model\HotelT;
use app\lib\exception\ParameterException;
use app\lib\exception\SaveException;
use EasyWeChat\Factory;

class HotelService
{
    public function createQRCode($hotel_id)
    {
        $company_id = Token::getCurrentTokenVar('company_id');
        $config = [
            'app_id' => config("wx.$company_id.app_id"),
            'secret' => config("wx.$company_id.app_secret")
        ];

        $hotel = HotelT::where('id', $hotel_id)->find();
        if (empty($hotel['lat']) || empty($hotel['lng'])) {
            throw new SaveException(['msg' => '酒店地理位置未设置']);
        }
        $path = 'pages/index/index?hotel_id=%s&lat=%s&lng=%s';
        $path = sprintf($path, $hotel_id, $hotel['lat'], $hotel['lng']);
        $app = Factory::miniProgram($config);
        $response = $app->app_code->get($path, [
            'width' => 600,
            'line_color' => [
                'r' => 105,
                'g' => 166,
                'b' => 134,
            ],
        ]);
        if ($response instanceof \EasyWeChat\Kernel\Http\StreamResponse) {
            $savePath = dirname($_SERVER['SCRIPT_FILENAME']) . '/static/qrcode';
            $filename = $response->saveAs($savePath, $hotel_id . '.png');
            $qrcode_url = '/static/qrcode' . '/' . $filename;
            $hotel->qrcode = $qrcode_url;
            $hotel->save();
            return config('setting.domain') . $qrcode_url;

        } else {
            throw new SaveException(['msg' => '生成二维码失败']);
        }
    }

}