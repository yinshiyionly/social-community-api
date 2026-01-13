<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAppPostTopicTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('app_post_topic', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('post_id')->comment('帖子ID');
            $table->bigInteger('topic_id')->comment('话题ID');
            $table->timestamp('created_at')->useCurrent();

            $table->index('topic_id', 'idx_pt_topic');
            $table->unique(['post_id', 'topic_id'], 'uk_post_topic');
        });

        DB::statement("COMMENT ON TABLE app_post_topic IS '帖子话题关联表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('app_post_topic');
    }
}
