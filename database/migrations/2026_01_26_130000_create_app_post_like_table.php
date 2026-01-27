<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateAppPostLikeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('app_post_like', function (Blueprint $table) {
            $table->bigIncrements('like_id')->comment('点赞ID');
            $table->bigInteger('member_id')->comment('会员ID');
            $table->bigInteger('post_id')->comment('帖子ID');
            $table->timestamps();

            // 唯一索引，防止重复点赞
            $table->unique(['member_id', 'post_id'], 'app_post_like_uk_member_post');
            // 查询索引
            $table->index('member_id', 'idx_like_member');
            $table->index('post_id', 'idx_like_post');
        });

        // 添加表注释
        DB::statement("COMMENT ON TABLE app_post_like IS '帖子点赞表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('app_post_like');
    }
}
