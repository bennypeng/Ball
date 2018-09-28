<?php

namespace App\Http\Controllers;

use App\WxUser;
use App\UserBag;
use App\Shop;
//use App\Snail;
use Carbon\Carbon;
use WXBizDataCrypt;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class WxUserController extends Controller
{
    protected $wxUserModel;
    protected $userBagModel;
    protected $shopModel;
//    protected $snailModel;
//    protected $configModel;

    public function __construct()
    {
        $this->wxUserModel  = new WxUser;
        $this->userBagModel = new UserBag;
        $this->shopModel    = new Shop;
//        $this->chapterModel = new Chapter;
//        $this->configModel  = new \App\Config;
    }

    /**
     * 用户登录
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function login(Request $req)
    {

        $jsCode        = $req->get('js_code', '');
        $encryptedData = $req->get('encryptedData', '');
        $iv            = $req->get('iv', '');
        $sessionId     = $req->header('sessionId', '');
        $signature     = $req->get('signature', '');
        $rawData       = $req->get('rawData', '');


//        $openId = "oFulc5ccnq0bkolvgwZ_7w6ywIyI";
//        $sessionKey = "hnWyfjYUZw1rwaBgW9fhfg==";
        // 获取session_key 和 openId
        $sessionData   = $this->_getSessionData($jsCode);

        Log::info('sessionId：' . $sessionId . ', sessionData：', $sessionData);

        // 网络请求失败
        if (!$sessionData)
        {
            return response()->json(Config::get('constants.NETWORK_ERROR'));
        }

        // 数据类型错误
        if (!is_array($sessionData))
        {
            return response()->json(Config::get('constants.DATA_TYPE_ERROR'));
        }

        // 内部错误
        if (isset($sessionData['errcode']) && $sessionData['errcode'])
        {
            Log::error('/api/user/login', $sessionData);
            return response()->json(Config::get('constants.INTERNAL_ERROR'));
        }

        $openId     = $sessionData['openid'];
        $sessionKey = $sessionData['session_key'];

        // 数据签名校验
        if ($rawData && !$sessionId && $signature != sha1($rawData . $sessionKey))
        {
            return response()->json(Config::get('constants.SIGNATURE_ERROR'));
        }

        // 判断sessionId的合法性
        if ($sessionId && $sessionId != 'undefined')
        {
            $key = $this->wxUserModel->getUserSessionIdKey($sessionId);

            if (Redis::exists($key))
            {
                if (Redis::hget($key, 'openId') != $openId)
                {
                    // openId不一致，需要重新登陆
                    return response()->json(Config::get('constants.OPENID_ERROR'));
                }
            } else {
                // sessionId已过期
                return response()->json(Config::get('constants.SESSIONID_EXP_ERROR'));
            }

            $userData = $this->wxUserModel->getUserByOpenId($openId);
        } else {

            $userData = $this->wxUserModel->getUserByOpenId($openId);

            if (!$userData)
            {
                // 如果找不到该openId， 则进行注册
                $pc = new WXBizDataCrypt(env('APPID'), $sessionKey);
                $errCode = $pc->decryptData($encryptedData, $iv, $data );
                if ($errCode == 0)
                {
                    $dataArr = json_decode($data, true);

                    $update = [
                        'openId'     => $openId,
                        'cId'        => '',
                        'gold'       => '0',
                        'diamond'    => 0,
                        'vip'        => 0,
                        'level'      => 1,
                        'levelPoint' => 0,
                        'gender'     => $dataArr['gender'],
                        'gender'     => $dataArr['gender'],
                        'avatarUrl'  => $dataArr['avatarUrl'],
                        'language'   => $dataArr['language'],
                        'nickName'   => $dataArr['nickName'],
                        'country'    => $dataArr['country'],
                        'province'   => $dataArr['province'],
                        'city'       => $dataArr['city'],
                    ];

                    $userId = $this->wxUserModel->registerUser($update);

                    // 注册成功， 则生成sessionId返回给客户端
                    if ($userId)
                    {
                        $sessionId = $this->_3rd_session(16);
                        $key       = $this->wxUserModel->getUserSessionIdKey($sessionId);
                        $this->wxUserModel->setUserSessionId($key, [
                            'openId'      => $openId,
                            'session_key' => $sessionKey,
                            'userId'      => $userId
                        ]);

                        $userData = $this->wxUserModel->getUserByOpenId($openId);

                        Log::info('创建用户成功，userId:' . $userId);

                    } else {
                        // 注册失败
                        Log::error('注册失败，update: ', $update);

                        return response()->json(Config::get('constants.REG_ERROR'));
                    }
                } else {
                    // 解密失败
                    Log::error(sprintf('sessionKey: %s, encryptedData: %s, iv: %s, errcode: %s', $sessionKey, $encryptedData, $iv, $errCode));
                    return response()->json(Config::get('constants.DECODE_ERROR'));
                }
            } else {
                // 如果能找到openId， 则进行生成sessionId
                $sessionId = $this->_3rd_session(16);
                $key       = $this->wxUserModel->getUserSessionIdKey($sessionId);
                $this->wxUserModel->setUserSessionId($key, [
                    'openId'      => $openId,
                    'session_key' => $sessionKey,
                    'userId'      => $userData['id']
                ]);
            }
        }

        // 更新sessionKey
        $key = $this->wxUserModel->getUserSessionIdKey($sessionId);
        $this->wxUserModel->setUserSessionId($key, [
            'session_key' => $sessionKey
        ]);

        $userId          = $this->wxUserModel->getUserIdBySessionId($sessionId);

        //dd($this->userBagModel->getUserBag($userId));
        //dd($this->userBagModel->createUserItem(['userId' => $userId, 'itemId' => 'B003']));
        //dd($this->userBagModel->levelupUserItem($userId, 'B001'));
        //return response()->json($this->userBagModel->getUserBagBuyList($userId));
        //dd($this->shopModel->getShopConf());

        return response()->json(
            array_merge(
                array(
                    'sessionId'    => $sessionId,
                    'userId'       => $userData['id'],
                    'offlineGold'  => 9999999,
                    'level'        => $userData['level'],
                    'levelPoint'   => $userData['levelPoint'],
                    'gold'         => $userData['gold'],
                    'diamond'      => $userData['diamond'],
                    'vip'          => $userData['vip'],
                    'world'        => 0,
                    'loginAward'   => 1,
                    'hinderMap'    => [],
                    'shopList'     => $this->userBagModel->getUserBagBuyList($userId, false)
                ),
                Config::get('constants.SUCCESS')
            )
        );
    }

    public function sync(Request $req)
    {
        /**
         * @todo
         */
    }

    /**
     * 通过js_code获取 session_key 和 openid
     * @param string $jsCode
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function _getSessionData($jsCode = '') {

        $client = new Client;
        $resp = $client->request('GET', 'https://api.weixin.qq.com/sns/jscode2session', [
            'headers' => [
                'Content-Type' => 'application/json; charset=utf-8'
            ],
            'query' => [
                'appid' => env('APPID'),
                'secret' => env('APPSECRET'),
                'js_code' => $jsCode,
                'grant_type' => 'authorization_code'
            ]
        ]);

        if ($resp->getStatusCode() == 200) {
            $resArr = json_decode($resp->getBody(), true);
            return $resArr;
        }

        return false;
    }

    /**
     * 生成3rd_session
     * @param $len
     * @return bool|string
     */
    private function _3rd_session($len)
    {
        $result = '';
        $fp = @fopen('/dev/urandom', 'rb');

        if ($fp !== FALSE)
        {
            $result .= @fread($fp, $len);
            @fclose($fp);
        } else {
            Log::error('Can not open /dev/urandom.');
            return false;
        }

        $result = strtr(base64_encode($result), '+/', '-_');

        return substr($result, 0, $len);
    }
}
