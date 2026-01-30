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
        // 1. 创建统计表
        Schema::create('app_post_stat', function (Blueprint $table) {
            $table->bigInteger('post_id')->primary();
            $table->integer('view_count')->default(0);
            $table->integer('like_count')->default(0);
            $table->integer('comment_count')->default(0);
            $table->integer('share_count')->default(0);
            $table->integer('collection_count')->default(0);
            $table->timestamps();

            $table->comment('动态统计表');
        });

        // 添加字段注释
        DB::statement("COMMENT ON COLUMN app_post_stat.view_count IS '浏览数'");
        DB::statement("COMMENT ON COLUMN app_post_stat.like_count IS '点赞数'");
        DB::statement("COMMENT ON COLUMN app_post_stat.comment_count IS '评论数'");
        DB::statement("COMMENT ON COLUMN app_post_stat.share_count IS '分享数'");
        DB::statement("COMMENT ON COLUMN app_post_stat.collection_count IS '收藏数'");

        // 2. 迁移现有数据
        DB::statement("
            INSERT INTO app_post_stat (post_id, view_count, like_count, comment_count, share_count, collection_count, created_at, updated_at)
            SELECT post_id, view_count, like_count, comment_count, share_count, collection_count, created_at, updated_at
            FROM app_post_base
            WHERE deleted_at IS NULL
        ");

        // 3. 删除旧字段
        Schema::table('app_post_base', function (Blueprint $table) {
            $table->dropColumn([
                'view_count',
                'like_count',
                'comment_count',
                'share_count',
                'collection_count',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // 1. 恢复旧字段
        Schema::table('app_post_base', function (Blueprint $table) {
            $table->integer('view_count')->default(0);
            $table->integer('like_count')->default(0);
            $table->integer('comment_count')->default(0);
            $table->integer('share_count')->default(0);
            $table->integer('collection_count')->default(0);
        });

        // 2. 恢复数据
        DB::statement("
            UPDATE app_post_base
            SET view_count = s.view_count,
                like_count = s.like_count,
                comment_count = s.comment_count,
                share_count = s.share_count,
                collection_count = s.collection_count
            FROM app_post_stat s
            WHERE app_post_base.post_id = s.post_id
        ");

        // 3. 删除统计表
        Schema::dropIfExists('app_post_stat');
    }
}
