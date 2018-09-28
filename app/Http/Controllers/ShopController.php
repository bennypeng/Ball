<?php

namespace App\Http\Controllers;

//use App\Services\HelperService;
use App\UserBag;
use App\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class ShopController extends Controller
{
    protected $userBagModel;
    protected $shopModel;

    public function __construct()
    {
        $this->userBagModel = new UserBag;
        $this->shopModel    = new Shop;
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

        $shopConf = $this->shopModel->getShopConf();

        $userBuyList = $this->userBagModel->getUserBagBuyList($userId, true);

        $itemInfo = $shopConf[$itemId];

        // 没有找到物品配置
        if (!isset($shopConf[$itemId]) || !isset($userBuyList[$itemInfo['groupTab']][$itemId]))
        {
            return response()->json(Config::get('constants.CONF_ERROR'));
        }

        list($itemId, $itemLevel, $itemAttack, $itemCostVal, $itemCostType, $itemUnlock) = $userBuyList[$itemInfo['groupTab']][$itemId];

        // 钱不够
        if ($itemUnlock == 3)
        {
            return response()->json(Config::get('constants.GOLD_NOT_ENOUGH'));
        }



        //$this->userBagModel->createUserItem(['userId' => $userId, 'itemId' => 'B003'])


        //if ($userBuyList[$itemInfo['groupTab']][$itemId])

        //dd($userBuyList[$itemInfo['groupTab']][$itemId], $shopConf[$itemId], $userBuyList);

        //$this->userBagModel->createUserItem(['userId' => $userId, 'itemId' => $itemId]);

        return response()->json(
            array_merge(
                [
                    'itemChange' => [
                        $itemInfo['groupTab'] => [
                            $itemId,
                            $itemLevel,
                            $itemAttack,
                            $itemCostVal,
                            $itemCostType,
                            $itemUnlock
                        ]
                    ]
                ],
                Config::get('constants.SUCCESS')
            )
        );
    }


}
