<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAppTopicBaseTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('app_topic_base', function (Blueprint $table) {
            $table->bigIncrements('topic_id')->comment('话题ID');
            $table->string('topic_name', 100)->default('')->comment('话题名称');
            $table->string('cover_url', 500)->default('')->comment('封面图URL');
            $table->string('description', 500)->default('')->comment('话题描述');
            $table->integer('view_count')->default(0)->comment('浏览数');
            $table->integer('post_count')->default(0)->comment('帖子数');
            $table->integer('sort_num')->default(0)->comment('排序号');
            $table->smallInteger('is_recommend')->default(0)->comment('是否推荐 0否 1是');
            $table->smallInteger('status')->default(1)->comment('状态 1正常 2禁用');
            $table->timestamps();
            $table->softDeletes();

            $table->index('sort_num', 'idx_topic_sort');
            $table->unique('topic_name', 'uk_topic_name');
        });

        DB::statement("COMMENT ON TABLE app_topic_base IS '话题基础表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('app_topic_base');
    }
}
