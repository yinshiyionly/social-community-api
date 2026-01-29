<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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
            $table->string('topic_name', 100)->comment('话题名称');
            $table->string('cover_url', 500)->default('')->comment('封面图URL');
            $table->string('description', 500)->default('')->comment('话题简介');
            $table->text('detail_html')->nullable()->comment('话题详情（富文本HTML）');
            $table->bigInteger('creator_id')->default(0)->comment('创建者ID');
            $table->integer('sort_num')->default(0)->comment('排序号（越大越靠前）');
            $table->smallInteger('is_recommend')->default(0)->comment('是否推荐 0否 1是');
            $table->smallInteger('is_official')->default(0)->comment('是否官方话题 0否 1是');
            $table->smallInteger('status')->default(1)->comment('状态 1正常 2禁用');
            $table->timestamps();
            $table->softDeletes();

            $table->unique('topic_name', 'uk_topic_name');
            $table->index('status', 'idx_topic_status');
            $table->index(['is_recommend', 'sort_num'], 'idx_topic_recommend');
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
