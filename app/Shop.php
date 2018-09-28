<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis;

class Shop extends Model
{
    protected $table      = 'shops';
    protected $primaryKey = 'id';
    public    $timestamps = false;

    /**
     * 获取商店配置
     * @param int $itemType
     * @return array
     */
    public function getShopConf($itemType = -1)
    {
        $key = $this->_getShopKey($itemType);

        if (!Redis::exists($key))
        {
            if ($itemType == -1)
            {
                $shopConfigObj = Shop::all();
            } else
            {
                $shopConfigObj = Shop::where('itemType', '=', $itemType)->get();
            }

            if (!$shopConfigObj) return array();

            $shopConfig = $shopConfigObj->toArray();

            $shopKeyConf = [];

            foreach($shopConfig as $k => $v) {
                $shopKeyConf[$v['itemId']] = json_encode($v);
            }

            Redis::hmset($key, $shopKeyConf);
        }

        $shopConfig = Redis::hgetall($key);

        foreach($shopConfig as $k => &$v) {
            $v = json_decode($v, true);
        }

        unset($v);

        ksort($shopConfig);

        return $shopConfig;
    }

    private function _getShopKey($itemType = -1)
    {
        if ($itemType == -1) {
            return 'BALL_CONFIG_SHOP';
        }
        return 'BALL_CONFIG_SHOP_' . $itemType;
    }

}
