<?php

namespace App;

use App\Shop;
use App\WxUser;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class UserBag extends Model
{
    protected $table = 'users_bags';
    protected $primaryKey = 'id';
    public $timestamps = false;

    /**
     * 获取用户背包
     * @param string $userId
     * @return array
     */
    public function getUserBag($userId = '')
    {
        if (!$userId) return array();

        $key = $this->_getUserBagKey($userId);

        if (!Redis::exists($key))
        {
            $userBag = UserBag::where('userId', $userId)->get();

            $userBagArr = $userBag->toArray();

            if (!$userBagArr) return array();

            $arr = [];

            foreach ($userBagArr as $v)
            {
                $arr[$v['itemId']] = $v['level'];
            }

            Redis::hmset($key, $arr);

        } else {
            $userBag = Redis::hgetall($key);
        }

        if (!is_array($userBag))
        {
            $userBag = $userBag->toArray();
        }

        return $userBag;
    }

    /**
     * 增加背包物品
     * @param string $userId
     * @param array $data
     * @return bool
     */
    public function createUserItem($data = [])
    {
        if (!$data) return false;

        $userBag = $this->getUserBag($data['userId']);

        // 已经拥有了该物品
        if (isset($userBag[$data['itemId']])) return true;

        $key = $this->_getUserBagKey($data['userId']);

        // 添加失败
        if (!UserBag::insertGetId($data)) return false;

        Redis::hset($key, $data['itemId'], 1);

        return true;
    }

    /**
     * 升级用户物品
     * @param string $userId
     * @param string $itemId
     * @return bool
     */
    public function levelupUserItem($userId = '', $itemId = '')
    {
        if (!$userId || !$itemId) return false;

        $key = $this->_getUserBagKey($userId);

        if (!Redis::hexists($key, $itemId)) return false;

        $level = Redis::hincrby($key, $itemId, 1);

        if (!UserBag::where('userId', $userId)->where('itemId', $itemId)->update(['level' => $level])) return false;

        return true;
    }

    /**
     * 获取购买列表
     * @param string $userId
     * @return array
     */
    public function getUserBagBuyList($userId = '')
    {
        if (!$userId) return array();

        $userBag = $this->getUserBag($userId);

        $shopModel = new Shop();

        $shopConf = $shopModel->getShopConf();

        $ret = [];

        $myLevel = 100;  // 需要获取我的等级

        $myGold  = 10000; // 需要获取我的金币

        $myDiamond = 10000; // 需要获取我的钻石

        foreach($shopConf as $k => $v)
        {
            $attack   = $v['initVal'];  // 需要计算伤害
            $level    = $v['initLevel']; // 需要计算当前等级
            $costVal  = $v['costVal'];  // 需要计算当前升级花费
            $costType = $v['costType'];

            // 解锁状态
            if ($myLevel >= $v['unLockedLevel'])
            {
                // 分享或广告类型
                if (in_array($costType, [3, 4]))
                {
                    $unLocked = 1;
                } else {
                    if ($costType == 1)
                    {
                        // 金币
                        if (!isset($userBag[$k]))
                        {
                            $unLocked = $myGold >= $costVal ? 1 : 0;
                        } else {
                            $unLocked = $myGold >= $costVal ? 2 : 3;
                        }
                    } else if ($costType == 2)
                    {
                        // 钻石
                        if (!isset($userBag[$k]))
                        {
                            $unLocked = $myDiamond >= $costVal ? 1 : 0;
                        } else {
                            $unLocked = $myDiamond >= $costVal ? 2 : 3;
                        }
                    }
                }
            } else {
                $unLocked = 0;
            }

            $ret[$v['groupTab']][] = [
                (string)$v['itemId'],
                (string)$level,
                (string)$attack,
                (string)$costVal,
                (string)$v['costType'],
                (string)$unLocked
            ];

        }

        return $ret;
    }


    private function _getUserBagKey($userId = '')
    {
        return 'BALL_U_BAG_' . $userId;
    }

}
