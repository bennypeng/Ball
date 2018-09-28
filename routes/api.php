<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

//  需要带上sessionId
Route::group(['middleware' => 'skey'], function() {

    //  同步用户数据
    Route::post('user/sync', 'WxUserController@sync');

    //  购买（升级）物品（buff）
    Route::post('shop/buy', 'ShopController@buy');

});

//  登录授权
Route::post('user/login', 'WxUserController@login');

//  错误返回
Route::fallback(function (){
    return response()->json(['message' => 'Not Found!', 'code' => 404], 404);
});