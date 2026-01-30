<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateAppPostStatTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('app_post_stat', function (Blueprint $table) {
            $table->bigInteger('post_id')->primary()->comment('帖子ID');
            $table->integer('view_count')->default(0)->comment('浏览数');
            $table->integer('like_count')->default(0)->comment('点赞数');
            $table->integer('comment_count')->default(0)->comment('评论数');
            $table->integer('share_count')->default(0)->comment('分享数');
            $table->integer('collection_count')->default(0)->comment('收藏数');
            $table->timestamps();
        });

        // 表注释
        DB::statement("COMMENT ON TABLE app_post_stat IS '动态统计表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('app_post_stat');
    }
}
