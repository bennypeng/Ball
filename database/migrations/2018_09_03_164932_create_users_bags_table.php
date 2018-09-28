<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersBagsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users_bags', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('userId')->unsigned()->comment('用户ID');
            $table->string('itemId', 30)->comment('物品Id');
            //$table->tinyInteger('itemType')->default(0)->comment('物品类型0点击1效率2收益3伤害4离线收益5普通球6魔法球');
            $table->integer('level')->unsigned()->default(1)->comment('物品等级');
            $table->index('userId');
            $table->unique(['userId', 'itemId']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users_bags');
    }
}
