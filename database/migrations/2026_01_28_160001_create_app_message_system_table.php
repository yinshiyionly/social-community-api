<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateAppMessageSystemTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('app_message_system', function (Blueprint $table) {
            $table->bigIncrements('message_id')->comment('消息ID');
            $table->bigInteger('receiver_id')->nullable()->comment('接收者ID（NULL表示全员消息）');
            $table->string('title', 100)->comment('消息标题');
            $table->text('content')->comment('消息内容');
            $table->string('cover_url', 500)->nullable()->comment('封面图URL');
            $table->smallInteger('link_type')->nullable()->comment('跳转类型：1=帖子详情 2=活动页 3=外链 4=无跳转');
            $table->string('link_url', 500)->nullable()->comment('跳转链接/目标ID');
            $table->smallInteger('is_read')->default(0)->comment('是否已读');
            $table->timestamps();

            // 索引
            $table->index(['receiver_id', 'created_at'], 'idx_system_receiver');
        });

        // 添加表注释
        DB::statement("COMMENT ON TABLE app_message_system IS '系统消息表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('app_message_system');
    }
}
