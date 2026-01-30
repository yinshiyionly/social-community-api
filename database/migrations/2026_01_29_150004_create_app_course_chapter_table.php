<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateAppCourseChapterTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 章节基础表（公共字段）
        Schema::create('app_course_chapter', function (Blueprint $table) {
            $table->bigIncrements('chapter_id')->comment('章节ID');
            $table->bigInteger('course_id')->comment('课程ID');
            $table->integer('chapter_no')->comment('章节序号');
            $table->string('chapter_title', 200)->comment('章节标题');
            $table->string('chapter_subtitle', 300)->nullable()->comment('章节副标题');
            $table->string('cover_image', 500)->nullable()->comment('章节封面');
            $table->text('brief')->nullable()->comment('章节简介');
            
            // 解锁配置
            $table->smallInteger('is_free')->default(0)->comment('是否免费试看：0否 1是');
            $table->smallInteger('is_preview')->default(0)->comment('是否先导课：0否 1是');
            $table->smallInteger('unlock_type')->default(1)->comment('解锁类型：1立即解锁 2按天数解锁 3按日期解锁');
            $table->integer('unlock_days')->default(0)->comment('解锁天数（相对于领取/购买日期）');
            $table->date('unlock_date')->nullable()->comment('固定解锁日期');
            $table->time('unlock_time')->nullable()->comment('解锁时间点');
            
            // 打卡作业配置
            $table->smallInteger('has_homework')->default(0)->comment('是否有作业：0否 1是');
            $table->smallInteger('homework_required')->default(0)->comment('作业是否必做：0否 1是');
            
            // 学习配置
            $table->integer('duration')->default(0)->comment('时长（秒）');
            $table->integer('min_learn_time')->default(0)->comment('最少学习时长（秒）');
            $table->smallInteger('allow_skip')->default(0)->comment('允许跳过：0否 1是');
            $table->smallInteger('allow_speed')->default(1)->comment('允许倍速：0否 1是');
            
            // 统计
            $table->integer('view_count')->default(0)->comment('观看次数');
            $table->integer('complete_count')->default(0)->comment('完成人数');
            $table->integer('homework_count')->default(0)->comment('作业提交数');
            
            // 排序与状态
            $table->integer('sort_order')->default(0)->comment('排序');
            $table->smallInteger('status')->default(1)->comment('状态：0草稿 1上架 2下架');
            
            $table->string('create_by', 64)->nullable();
            $table->timestamp('create_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->string('update_by', 64)->nullable();
            $table->timestamp('update_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->smallInteger('del_flag')->default(0)->comment('删除标志');

            $table->index('course_id', 'idx_chapter_course');
            $table->index(['course_id', 'chapter_no'], 'idx_chapter_course_no');
            $table->index(['course_id', 'status', 'sort_order'], 'idx_chapter_list');
        });

        DB::statement("COMMENT ON TABLE app_course_chapter IS '课程章节基础表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('app_course_chapter');
    }
}
