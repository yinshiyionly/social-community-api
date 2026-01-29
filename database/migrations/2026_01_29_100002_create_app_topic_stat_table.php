<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateAppTopicStatTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('app_topic_stat', function (Blueprint $table) {
            $table->bigInteger('topic_id')->primary()->comment('话题ID');
            $table->integer('post_count')->default(0)->comment('帖子数');
            $table->integer('view_count')->default(0)->comment('浏览数');
            $table->integer('follow_count')->default(0)->comment('关注数');
            $table->integer('participant_count')->default(0)->comment('参与人数');
            $table->integer('today_post_count')->default(0)->comment('今日新增帖子数');
            $table->decimal('heat_score', 12, 4)->default(0)->comment('热度分');
            $table->timestamp('last_post_at')->nullable()->comment('最后发帖时间');
            $table->timestamps();

            $table->index('heat_score', 'idx_topic_heat');
            $table->index('post_count', 'idx_topic_post_count');
        });

        DB::statement("COMMENT ON TABLE app_topic_stat IS '话题统计表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('app_topic_stat');
    }
}
