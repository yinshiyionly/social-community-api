<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateAppTopicPostRelationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('app_topic_post_relation', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('topic_id')->comment('话题ID');
            $table->bigInteger('post_id')->comment('帖子ID');
            $table->bigInteger('member_id')->comment('发帖人ID');
            $table->smallInteger('is_featured')->default(0)->comment('是否精选 0否 1是');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['topic_id', 'post_id'], 'uk_topic_post');
            $table->index(['topic_id', 'created_at'], 'idx_topic_posts');
            $table->index('post_id', 'idx_post_topics');
            $table->index(['topic_id', 'member_id'], 'idx_topic_member');
        });

        DB::statement("COMMENT ON TABLE app_topic_post_relation IS '帖子话题关联表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('app_topic_post_relation');
    }
}
