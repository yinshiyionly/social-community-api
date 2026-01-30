<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateAppCourseHomeworkTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 章节作业配置表
        Schema::create('app_chapter_homework', function (Blueprint $table) {
            $table->bigIncrements('homework_id')->comment('作业ID');
            $table->bigInteger('chapter_id')->comment('章节ID');
            $table->bigInteger('course_id')->comment('课程ID');
            $table->string('homework_title', 200)->comment('作业标题');
            $table->text('homework_content')->nullable()->comment('作业要求');
            $table->smallInteger('homework_type')->default(1)->comment('作业类型：1图文打卡 2视频打卡 3问答 4文件提交');
            $table->jsonb('homework_config')->default('{}')->comment('作业配置');
            $table->integer('point_reward')->default(0)->comment('完成奖励积分');
            $table->integer('deadline_days')->default(0)->comment('截止天数（0=不限）');
            $table->smallInteger('need_review')->default(0)->comment('是否需要批改：0否 1是');
            $table->smallInteger('show_others')->default(1)->comment('是否展示他人作业：0否 1是');
            $table->integer('submit_count')->default(0)->comment('提交数');
            $table->integer('sort_order')->default(0)->comment('排序');
            $table->smallInteger('status')->default(1)->comment('状态：1启用 2禁用');
            $table->timestamp('create_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('update_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));

            $table->index('chapter_id', 'idx_homework_chapter');
            $table->index('course_id', 'idx_homework_course');
        });
        DB::statement("COMMENT ON TABLE app_chapter_homework IS '章节作业配置表'");

        // 用户作业提交表
        Schema::create('app_member_homework_submit', function (Blueprint $table) {
            $table->bigIncrements('submit_id')->comment('提交ID');
            $table->bigInteger('homework_id')->comment('作业ID');
            $table->bigInteger('chapter_id')->comment('章节ID');
            $table->bigInteger('course_id')->comment('课程ID');
            $table->bigInteger('member_id')->comment('用户ID');
            $table->text('submit_content')->nullable()->comment('提交内容');
            $table->jsonb('submit_images')->default('[]')->comment('提交图片');
            $table->jsonb('submit_videos')->default('[]')->comment('提交视频');
            $table->jsonb('submit_files')->default('[]')->comment('提交文件');
            $table->smallInteger('review_status')->default(0)->comment('批改状态：0待批改 1已通过 2需修改');
            $table->text('review_content')->nullable()->comment('批改内容');
            $table->bigInteger('reviewer_id')->nullable()->comment('批改人ID');
            $table->timestamp('review_time')->nullable()->comment('批改时间');
            $table->integer('point_earned')->default(0)->comment('获得积分');
            $table->integer('like_count')->default(0)->comment('点赞数');
            $table->integer('comment_count')->default(0)->comment('评论数');
            $table->smallInteger('is_excellent')->default(0)->comment('是否优秀作业');
            $table->string('client_ip', 50)->nullable();
            $table->timestamp('create_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('update_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));

            $table->unique(['homework_id', 'member_id'], 'uk_homework_member');
            $table->index('member_id', 'idx_submit_member');
            $table->index('course_id', 'idx_submit_course');
            $table->index('review_status', 'idx_submit_review_status');
        });
        DB::statement("COMMENT ON TABLE app_member_homework_submit IS '用户作业提交表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('app_member_homework_submit');
        Schema::dropIfExists('app_chapter_homework');
    }
}
