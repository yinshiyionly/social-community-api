<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateAppCourseBaseTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('app_course_base', function (Blueprint $table) {
            $table->bigIncrements('course_id')->comment('课程ID');
            $table->string('course_no', 32)->comment('课程编号');
            $table->integer('category_id')->comment('分类ID');
            $table->string('course_title', 200)->comment('课程标题');
            $table->string('course_subtitle', 300)->nullable()->comment('课程副标题');
            
            // 课程类型
            $table->smallInteger('pay_type')->default(1)->comment('付费类型：1体验课 2小白课 3进阶课 4付费课');
            $table->smallInteger('play_type')->default(1)->comment('播放类型：1图文课 2录播课 3直播课 4音频课');
            $table->smallInteger('schedule_type')->default(1)->comment('排课类型：1固定日期 2动态解锁');
            
            // 封面与媒体
            $table->string('cover_image', 500)->nullable()->comment('封面图');
            $table->string('cover_video', 500)->nullable()->comment('封面视频');
            $table->jsonb('banner_images')->default('[]')->comment('轮播图列表');
            $table->string('intro_video', 500)->nullable()->comment('课程介绍视频');
            
            // 简介内容
            $table->text('brief')->nullable()->comment('课程简介');
            $table->text('description')->nullable()->comment('课程详情（富文本）');
            $table->text('suitable_crowd')->nullable()->comment('适合人群');
            $table->text('learn_goal')->nullable()->comment('学习目标');
            
            // 讲师信息
            $table->bigInteger('teacher_id')->nullable()->comment('主讲师ID');
            $table->jsonb('assistant_ids')->default('[]')->comment('助教ID列表');
            
            // 价格信息
            $table->decimal('original_price', 10, 2)->default(0)->comment('原价');
            $table->decimal('current_price', 10, 2)->default(0)->comment('现价');
            $table->integer('point_price')->default(0)->comment('积分价格（可用积分抵扣）');
            $table->smallInteger('is_free')->default(0)->comment('是否免费：0否 1是');
            
            // 课程配置
            $table->integer('total_chapter')->default(0)->comment('总章节数');
            $table->integer('total_duration')->default(0)->comment('总时长（秒）');
            $table->integer('valid_days')->default(0)->comment('有效期天数（0=永久）');
            $table->smallInteger('allow_download')->default(0)->comment('允许下载：0否 1是');
            $table->smallInteger('allow_comment')->default(1)->comment('允许评论：0否 1是');
            $table->smallInteger('allow_share')->default(1)->comment('允许分享：0否 1是');
            
            // 统计数据
            $table->integer('enroll_count')->default(0)->comment('报名人数');
            $table->integer('view_count')->default(0)->comment('浏览次数');
            $table->integer('complete_count')->default(0)->comment('完课人数');
            $table->integer('comment_count')->default(0)->comment('评论数');
            $table->decimal('avg_rating', 2, 1)->default(5.0)->comment('平均评分');
            
            // 排序与状态
            $table->integer('sort_order')->default(0)->comment('排序');
            $table->smallInteger('is_recommend')->default(0)->comment('是否推荐：0否 1是');
            $table->smallInteger('is_hot')->default(0)->comment('是否热门：0否 1是');
            $table->smallInteger('is_new')->default(0)->comment('是否新课：0否 1是');
            $table->smallInteger('status')->default(0)->comment('状态：0草稿 1上架 2下架');
            $table->timestamp('publish_time')->nullable()->comment('上架时间');
            
            // 系统字段
            $table->string('create_by', 64)->nullable();
            $table->timestamp('create_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->string('update_by', 64)->nullable();
            $table->timestamp('update_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->smallInteger('del_flag')->default(0)->comment('删除标志：0正常 1删除');

            $table->unique(['course_no', 'del_flag'], 'uk_course_no');
            $table->index('category_id', 'idx_course_category');
            $table->index('pay_type', 'idx_course_pay_type');
            $table->index('play_type', 'idx_course_play_type');
            $table->index('teacher_id', 'idx_course_teacher');
            $table->index('status', 'idx_course_status');
            $table->index(['status', 'is_recommend', 'sort_order'], 'idx_course_list');
        });

        DB::statement("COMMENT ON TABLE app_course_base IS '课程基础表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('app_course_base');
    }
}
