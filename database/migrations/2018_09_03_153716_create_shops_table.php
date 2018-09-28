<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShopsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shops', function (Blueprint $table) {
            $table->increments('id');
            $table->string('itemId', 30)->comment('物品Id');
            $table->string('name', 50)->comment('名称');
            $table->string('initVal', 100)->default(-1)->comment('初始伤害值-1无初始值，如buff');
            $table->integer('initLevel')->default(1)->comment('初始等级');
            $table->tinyInteger('itemType')->default(0)->comment('物品类型0点击1效率2收益3伤害4离线收益5普通球6魔法球');
            $table->string('costVal', 100)->default(0)->comment('消耗值0无消耗值，如分享');
            $table->tinyInteger('costType')->default(1)->comment('消耗类型1金币2钻石3分享4广告');
            $table->integer('timeSec')->default(-1)->comment('持续秒数-1永久');
            $table->integer('unLockedLevel')->default(1)->comment('解锁关卡');
            $table->tinyInteger('groupTab')->default(1)->comment('所属分组1球球2技能3魔法4商店');
            $table->string('describe', 100)->nullable()->comment('备注');
            $table->index('itemId');
            $table->index('costType');
            $table->index('itemType');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shops');
    }
}
