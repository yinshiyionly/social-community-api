<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateAppMessageInteractionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('app_message_interaction', function (Blueprint $table) {
            $table->bigIncrements('message_id')->comment('消息ID');
            $table->bigInteger('receiver_id')->comment('接收者ID');
            $table->bigInteger('sender_id')->comment('发送者ID');
            $table->smallInteger('message_type')->comment('消息类型：1=点赞 2=收藏 3=评论 4=关注');
            $table->bigInteger('target_id')->nullable()->comment('目标ID（帖子ID/评论ID）');
            $table->smallInteger('target_type')->nullable()->comment('目标类型：1=帖子 2=评论');
            $table->string('content_summary', 100)->nullable()->comment('内容摘要');
            $table->string('cover_url', 500)->nullable()->comment('封面图URL');
            $table->smallInteger('is_read')->default(0)->comment('是否已读：0=未读 1=已读');
            $table->timestamps();

            // 索引
            $table->index(['receiver_id', 'message_type', 'created_at'], 'idx_interaction_receiver_type');
            $table->index(['receiver_id', 'is_read'], 'idx_interaction_receiver_read');
            $table->index('sender_id', 'idx_interaction_sender');
        });

        // 添加表注释
        DB::statement("COMMENT ON TABLE app_message_interaction IS '互动消息表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('app_message_interaction');
    }
}
