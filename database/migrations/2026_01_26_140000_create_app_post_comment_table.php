<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateAppPostCommentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('app_post_comment', function (Blueprint $table) {
            $table->bigIncrements('comment_id')->comment('评论ID');
            $table->bigInteger('post_id')->comment('帖子ID');
            $table->bigInteger('member_id')->comment('评论者ID');
            $table->bigInteger('parent_id')->default(0)->comment('父评论ID，0表示一级评论');
            $table->bigInteger('reply_to_member_id')->default(0)->comment('回复目标用户ID');
            $table->text('content')->comment('评论内容');
            $table->integer('like_count')->default(0)->comment('点赞数');
            $table->integer('reply_count')->default(0)->comment('回复数');
            $table->smallInteger('status')->default(1)->comment('状态: 0待审核 1正常 2已删除');
            $table->timestamps();
            $table->softDeletes();

            // 索引
            $table->index('post_id', 'idx_comment_post');
            $table->index('member_id', 'idx_comment_member');
            $table->index('parent_id', 'idx_comment_parent');
            $table->index(['post_id', 'status', 'created_at'], 'idx_comment_list');
        });

        // 添加表注释
        DB::statement("COMMENT ON TABLE app_post_comment IS '帖子评论表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('app_post_comment');
    }
}
