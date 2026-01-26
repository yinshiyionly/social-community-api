<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateAppPostCollectionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('app_post_collection', function (Blueprint $table) {
            $table->bigIncrements('collection_id')->comment('收藏ID');
            $table->bigInteger('member_id')->comment('会员ID');
            $table->bigInteger('post_id')->comment('帖子ID');
            $table->timestamps();

            // 唯一索引，防止重复收藏
            $table->unique(['member_id', 'post_id'], 'uk_member_post');
            // 查询索引
            $table->index('member_id', 'idx_collection_member');
            $table->index('post_id', 'idx_collection_post');
        });

        // 添加表注释
        DB::statement("COMMENT ON TABLE app_post_collection IS '帖子收藏表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('app_post_collection');
    }
}
