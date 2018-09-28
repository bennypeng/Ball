<?php

namespace App\Http\Controllers;

//use App\Services\HelperService;
use App\UserBag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class ShopController extends Controller
{
    protected $userBagModel;

    public function __construct()
    {
        $this->userBagModel = new UserBag;
    }

    /**
     * 购买（升级）物品（buff）
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     */
    public function buy(Request $req)
    {
        $userId  = $req->get('userId', '');

        $itemId = $req->get('itemId', '');

        // 参数错误
        if (!$itemId)
        {
            return response()->json(Config::get('constants.ARGS_ERROR'));
        }

        $userBuyList = $this->userBagModel->getUserBagBuyList($userId);

        $userSnail = $userBags['snailMap'];

        // 获得可插入的槽位
        $idx = array_search([], $userSnail);

        // 槽位已满
        //if (count(array_filter($userSnail)) >= 15)
        if (!$idx)
        {
            return response()->json(Config::get('constants.SEAT_FULL_ERROR'));
        }

        $snailList = $this->snailModel->getUserSnailBuyList($userId);

        // 未找到相关配置
        if (!isset($snailList[$snailId]))
        {
            return response()->json(Config::get('constants.CONF_ERROR'));
        }

        $snailConf = $snailList[$snailId];

        // 未解锁
        if ($snailConf[3] != 1)
        {
            return response()->json(Config::get('constants.UNLOCK_ERROR'));
        }

        // 消耗类型的检测
        if ($snailConf[1] == 1)
        {
            // 钻石不足
            if ($userBags['diamond'] < $snailConf[2])
            {
                return response()->json(Config::get('constants.DIAMOND_NOT_ENOUGH'));
            }

            // 增加蜗牛的购买次数
            $buyNum = $this->snailModel->setUserSnailBuyNums($userId, $snailId, 1);

            // 扣除钻石
            $userBags['diamond'] -= $snailConf[2];
            $update = [
                'diamond' => $userBags['diamond'],
                'item_' . $idx => '[' . $snailId . ', 0]'
            ];

        } else if ($snailConf[1] == 2)
        {
            // 金币不足
            if ($userBags['gold'] < $snailConf[2])
            {
                return response()->json(Config::get('constants.GOLD_NOT_ENOUGH'));
            }

            // 增加蜗牛的购买次数
            $buyNum = $this->snailModel->setUserSnailBuyNums($userId, $snailId);

            // 扣除金币
            $userBags['gold'] -= $snailConf[2];
            $update = [
                'gold' => $userBags['gold'],
                'item_' . $idx => '[' . $snailId . ', 0]'
            ];

        } else if ($snailConf[1] == 3)
        {
            // 今日观看视频的次数
            $vNums = $this->snailModel->getUserSnailVedioNums($userId);

            // 观看次数上限
            if ($vNums >= 6)
            {
                return response()->json(Config::get('constants.MAX_VEDIO_NUM_ERROR'));
            }

            // 增加观看次数
            $this->snailModel->incrUserSnailVedioNums($userId);

            $update = [
                'item_' . $idx => '[' . $snailId . ', 0]'
            ];

        } else {
            // 未定义的类型
            return response()->json(Config::get('constants.UNDEFINED_ERROR'));
        }

        if (isset($update) && $update)
        {
            Log::info('购买蜗牛，userId: ' . $userId . ', data: ', $update);

            // 操作失败
            if (!$this->userBagModel->setUserBag($userId, $update))
            {
                return response()->json(Config::get('constants.FAILURE'));
            }

            // 刷新背包内容

            $userBags['snailMap'][$idx] = [intval($snailId), 0];

            $userBags['snailMap'] = array_values($userBags['snailMap']);

            // 计算价格
            if (isset($buyNum))
            {
                $snailArrConf = $this->snailModel->getSnailConf();
//dd($snailConf);
                $refPrice = $snailConf[1] == 2
                    ? round($this->snailModel->calcSnailPrice($snailArrConf[$snailId - 1], $buyNum))
                    : round($this->snailModel->calcSnailDiamondPrice($snailArrConf[$snailId - 1], $buyNum));

                //$refPrice  = round($this->snailModel->calcSnailPrice($snailConf[$snailId - 1], $buyNum));
            } else {
                $refPrice = $snailConf[2];
            }

        } else {
            $refPrice = $snailConf[2];
        }

        return response()->json(
            array_merge(
                [
                    'userBags' => $userBags,
                    'refPrice' => $refPrice,
                    'changeSeats' => [$idx => [intval($snailId), 0]]
                ],
                Config::get('constants.SUCCESS')
            )
        );
    }


}
