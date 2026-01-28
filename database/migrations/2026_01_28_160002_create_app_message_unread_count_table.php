<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateAppMessageUnreadCountTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('app_message_unread_count', function (Blueprint $table) {
            $table->bigInteger('member_id')->primary()->comment('会员ID');
            $table->integer('like_count')->default(0)->comment('点赞未读数');
            $table->integer('collect_count')->default(0)->comment('收藏未读数');
            $table->integer('comment_count')->default(0)->comment('评论未读数');
            $table->integer('follow_count')->default(0)->comment('关注未读数');
            $table->integer('system_count')->default(0)->comment('系统消息未读数');
            $table->timestamps();
        });

        // 添加表注释
        DB::statement("COMMENT ON TABLE app_message_unread_count IS '消息未读数统计表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('app_message_unread_count');
    }
}
