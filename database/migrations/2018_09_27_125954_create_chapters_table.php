<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateChaptersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chapters', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('level')->comment('关卡');
            $table->string('hp', 100)->comment('单个血量');
            $table->integer('num')->default(1)->comment('数量');
            $table->tinyInteger('world')->default(0)->comment('世界0默认');
            $table->index('world');
            $table->unique(['level', 'world']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('chapters');
    }
}
