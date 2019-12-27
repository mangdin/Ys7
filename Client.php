<?php

namespace mangdin\Ys7;

use mangdin\Ys7\Model\AccessToken;
use think\facade\Cache;

/**
 * 客户端类，封装了萤石开放平台Api的操作
 *
 * 具体的接口规则可参考官方文档：https://open.ys7.com/doc/zh/book/index/user.html
 *
 * @package mangdin\Ys7
 */
class Client
{
    /**
     * @var string
     */
    private $appKey;

    /**
     * @var string
     */
    private $appSecret;

    /**
     * accessToken缓存名称前缀
     */
    const ACCESS_TOKEN_CACHE_PREFIX = 'Ys7AccessToken_';

    /**
     * 接口入口网址
     */
    const API_ENDPOINT = 'https://open.ys7.com/api/lapp';

    /**
     * @param string $appKey
     * @param string $appSecret
     *
     * @throws \think\Exception
     */
    public function __construct($appKey, $appSecret )
    {
        $appKey = trim($appKey);
        $appSecret = trim($appSecret);

        if (empty($appKey)) {
            throw new \Exception('app id is empty');
        }

        if (empty($appSecret)) {
            throw new \Exception('app secret is empty');
        }

        $this->appKey = $appKey;
        $this->appSecret = $appSecret;
    }

    /**
     * 获取accessToken
     *
     * @return string
     */
    public function getAccessToken()
    {
        //从缓存去读取
        $cacheKey = $this->getAccessTokenCacheKey($this->appKey);
        $accessToken = Cache::get($cacheKey);
        if ($accessToken) {
            return $accessToken;
        }

        $params = [
            'appKey' => $this->appKey,
            'appSecret' => $this->appSecret
        ];

        $result = $this->post(self::API_ENDPOINT . '/token/get', $params, false);

        //缓存永久存储，lifetime设为0
        Cache::set($cacheKey,$result['data']['accessToken'],604750);

        return $result['data']['accessToken'];
    }

    /**
     * 获取摄像头列表
     *
     * @param int $pageStart 分页起始页，从0开始
     * @param int $pageSize 分页大小，默认为10，最大为50
     * @return mixed
     * @throws \think\Exception
     */
    public function getCameraList($pageStart = 0, $pageSize = 10)
    {
        if ($pageSize > 50) {
            throw new \Exception('pageSize can\'t be greater than 50.');
        }

        $params = [
            'pageStart' => (int)$pageStart,
            'pageSize' => (int)$pageSize
        ];

        return $this->post(self::API_ENDPOINT . '/live/video/list', $params);
    }


    /**
     * 获取单个设备信息
     *
     * @param $deviceSerial 设备序列号
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \think\Exception
     */
    public function getCameraInfo($deviceSerial){
        $params = [
            'Resource' => 'Cam:'.$deviceSerial.':1',
        ];
        return $this->post(self::API_ENDPOINT . '/live/address/get', $params);
    }


    /**
     *  添加设备
     *
     * @param $deviceSerial 设备序列号,存在英文字母的设备序列号，字母需为大写
     * @param $validateCode 设备验证码，设备机身上的六位大写字母
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \think\Exception
     */
    public function addCamera($deviceSerial,$validateCode){
        $params = [
            'deviceSerial' => $deviceSerial,
            'validateCode' => $validateCode,
        ];
        return $this->post(self::API_ENDPOINT . '/device/add', $params);
    }


    /**
     *  删除设备
     *
     * @param $deviceSerial 设备序列号,存在英文字母的设备序列号，字母需为大写
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \think\Exception
     */
    public function deleteCamera($deviceSerial){
        $params = [
            'deviceSerial' => $deviceSerial,
        ];
        return $this->post(self::API_ENDPOINT . '/device/delete', $params);
    }


    /**
     * 获取EZOpen协议网址
     *
     * @param string $deviceSn 设备序列号
     * @param int $channelNo 通道号
     * @param int $videoLevel 视频质量 0-流畅，1-均衡，2-高清，3-超清
     * @param string $type 类型 支持live(实时视频)和rec(录像播放)
     * @param string $password 视频加密密码，即设备标签上的6位字母验证码，支持明文/密文两种格式,
     * @return string
     * @throws \think\Exception
     */
    public function getEzUrl($deviceSn, $channelNo, $videoLevel, $type = 'live', $password = '')
    {
        $type = strtolower($type);
        if (!in_array($type, ['live', 'rec'])) {
            throw new \Exception('Invalid type.');
        }
        if ($videoLevel == 0 || $videoLevel == 1) {
            $videoLevelStr = '';
        } else {
            $videoLevelStr = '.hd';
        }
        return 'ezopen://' . ($password ? $password . '@' : '') . 'open.ys7.com/' . $deviceSn . '/' . $channelNo . $videoLevelStr . '.' . $type;
    }

    /**
     * 获取accessToken保存在缓存中的键值
     *
     * @param string $appKey
     * @return string
     */
    private function getAccessTokenCacheKey($appKey)
    {
        return self::ACCESS_TOKEN_CACHE_PREFIX . $appKey;
    }

    /**
     * 萤石云接口post请求
     *
     * @param $url
     * @param array $params
     * @param bool $auth
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \think\Exception
     */
    private function post($url, $params = [], $auth = true)
    {
        if ($auth) {
            $params['accessToken'] = $this->getAccessToken();
        }
        $client = new \GuzzleHttp\Client(['verify' => false]);
        $response = $client ->post($url, [ 'form_params' => $params ]);
        $result = json_decode($response->getBody(), true);
        if ($result['code'] !== '200') {
            return ['code' => 500, 'data' => '', 'msg' => $result['msg'], 'icon' => 5, 'time' => 1500];
        }
        return $result;
    }

}
