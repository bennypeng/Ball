<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis;

class WxUser extends Model
{
    protected $table      = 'users';
    protected $primaryKey = 'id';

    /**
     * 注册用户
     * @param array $data
     * @return bool
     */
    public function registerUser($data = [])
    {

        if (
            count($data) == 0
            || !isset($data['openId'])
            || !$data['openId']
        ) return false;

        $userId = WxUser::insertGetId($data);

        $data['id'] = $userId;

        if ($userId)
        {
            Redis::hmset($this->_getOpenIdKey($data['openId']), $data);
            Redis::hmset($this->_getUserKey($userId), $data);
            return $userId;
        }

        return false;
    }

    /**
     * 更新用户信息
     * @param string $userId
     * @param array $update
     * @return bool
     */
    public function updateUser($userId = '', $update = [])
    {
        if (!$userId || !$update) return false;

        if (!WxUser::where('id', $userId)->update($update))
        {
            return false;
        }

        $key = $this->_getUserKey($userId);

        if (!Redis::exists($key))
        {
            $this->getUserByUserId($userId);
        }

        Redis::hmset($key, $update);

        return true;
    }

    /**
     * 通过openId获取用户信息
     * @param string $openId
     * @return array
     */
    public function getUserByOpenId($openId = '')
    {

        if (!$openId) return array();

        $key = $this->_getOpenIdKey($openId);

        if (Redis::exists($key))
        {
            return Redis::hgetall($key);
        }

        $userInfo = WxUser::where('openId', $openId)->first();

        if (!$userInfo) return array();

        Redis::hmset($key, $userInfo->toArray());

        return $userInfo;
    }

    /**
     * 通过userId获取用户信息
     * @param string $userId
     * @return array
     */
    public function getUserByUserId($userId = '')
    {

        if (!$userId) return array();

        $key = $this->_getUserKey($userId);

        if (Redis::exists($key))
        {
            return Redis::hgetall($key);
        }

        $userInfo = WxUser::where('id', $userId)->first();

        if (!$userInfo) return array();

        Redis::hmset($key, $userInfo->toArray());

        return $userInfo;
    }

    /**
     * 通过sessionId获取userId
     * @param string $sessionId
     * @return bool|string
     */
    public function getUserIdBySessionId($sessionId = '')
    {
        if (!$sessionId) return false;

        $sessionIdKey = $this->getUserSessionIdKey($sessionId);

        if (!Redis::exists($sessionIdKey)) return false;

        $userId = Redis::hget($sessionIdKey, 'userId');

        if (!$userId) return false;

        return $userId;
    }

    /**
     * 通过sessionId获取userId
     * @param string $sessionId
     * @return bool|string
     */
    public function getSKeyBySessionId($sessionId = '')
    {
        if (!$sessionId) return false;

        $sessionIdKey = $this->getUserSessionIdKey($sessionId);

        if (!Redis::exists($sessionIdKey)) return false;

        $sessionKey = Redis::hget($sessionIdKey, 'session_key');

        if (!$sessionKey) return false;

        return $sessionKey;
    }

    /**
     * 设置3rd_sessionId
     * @param $key
     * @param array $data
     * @return bool
     */
    public function setUserSessionId($key, $data = [])
    {

        if (!$key || !$data) return false;

        Redis::hmset($key, $data);

        Redis::expireAt($key, Carbon::parse('+30 days')->startOfDay()->timestamp);

        return true;
    }

    /**
     * 获取障碍物地图
     * @param string $userId
     * @return array
     */
    public function getUserHinderMap($userId = '')
    {
        if (!$userId) return [];

        $key = $this->_getUserHinderMapKey($userId);

        if (!Redis::exists($key)) return [];

        $hinderMap = Redis::hgetall($key);

        foreach($hinderMap as &$v)
        {
            $v = json_decode($v,true);
        }

        unset($v);

        return $hinderMap;
    }

    /**
     * 设置障碍物地图
     * @param string $userId
     * @param array $data
     * @return bool
     */
    public function setUserHinderMap($userId = '', $data = [])
    {
        if (!$userId || !$data) return false;

        foreach($data as &$v)
        {
            $v = json_encode($v);
        }

        unset($v);

        $key = $this->_getUserHinderMapKey($userId);

        Redis::hmset($key, $data);

        return true;
    }

    /**
     * 获取sessionkey
     * @param string $sessionId
     * @return string
     */
    public function getUserSessionIdKey($sessionId = '')
    {
        return 'BALL_SID_' . $sessionId;
    }

    /**
     * 障碍物key
     * @param string $userId
     * @return string
     */
    private function _getUserHinderMapKey($userId = '')
    {
        return 'BALL_U_HINDER_MAP_' . $userId;
    }

    private function _getUserKey($userId = '')
    {
        return 'BALL_U_' . $userId;
    }

    private function _getOpenIdKey($openId = '')
    {
        return 'BALL_' . $openId;
    }

}
