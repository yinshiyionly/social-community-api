<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateAppCourseLearningNoteTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 学习笔记表
        Schema::create('app_member_learning_note', function (Blueprint $table) {
            $table->bigIncrements('note_id')->comment('笔记ID');
            $table->bigInteger('member_id')->comment('用户ID');
            $table->bigInteger('course_id')->comment('课程ID');
            $table->bigInteger('chapter_id')->comment('章节ID');
            $table->integer('time_point')->default(0)->comment('时间点（秒）');
            $table->text('content')->nullable()->comment('笔记内容');
            $table->jsonb('images')->default('[]')->comment('笔记图片');
            $table->smallInteger('is_public')->default(0)->comment('是否公开：0私密 1公开');
            $table->integer('like_count')->default(0)->comment('点赞数');
            $table->timestamp('create_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('update_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->smallInteger('del_flag')->default(0)->comment('删除标志');

            $table->index('member_id', 'idx_note_member');
            $table->index(['member_id', 'course_id'], 'idx_note_course');
            $table->index(['member_id', 'chapter_id'], 'idx_note_chapter');
        });
        DB::statement("COMMENT ON TABLE app_member_learning_note IS '学习笔记表'");

        // 学习打卡记录表
        Schema::create('app_member_learning_checkin', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('ID');
            $table->bigInteger('member_id')->comment('用户ID');
            $table->bigInteger('course_id')->comment('课程ID');
            $table->bigInteger('chapter_id')->nullable()->comment('章节ID');
            $table->date('checkin_date')->comment('打卡日期');
            $table->integer('learn_duration')->default(0)->comment('学习时长（秒）');
            $table->integer('chapters_learned')->default(0)->comment('学习章节数');
            $table->text('summary')->nullable()->comment('学习总结');
            $table->jsonb('images')->default('[]')->comment('打卡图片');
            $table->integer('point_earned')->default(0)->comment('获得积分');
            $table->timestamp('create_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));

            $table->unique(['member_id', 'course_id', 'checkin_date'], 'uk_member_course_date');
            $table->index('member_id', 'idx_checkin_member');
            $table->index('checkin_date', 'idx_checkin_date');
        });
        DB::statement("COMMENT ON TABLE app_member_learning_checkin IS '学习打卡记录表'");

        // 课程问答表
        Schema::create('app_course_qa', function (Blueprint $table) {
            $table->bigIncrements('qa_id')->comment('问答ID');
            $table->bigInteger('course_id')->comment('课程ID');
            $table->bigInteger('chapter_id')->nullable()->comment('章节ID');
            $table->bigInteger('member_id')->comment('提问用户ID');
            $table->bigInteger('parent_id')->default(0)->comment('父级ID（回复时）');
            $table->text('content')->comment('内容');
            $table->jsonb('images')->default('[]')->comment('图片');
            $table->smallInteger('is_teacher_reply')->default(0)->comment('是否讲师回复');
            $table->integer('like_count')->default(0)->comment('点赞数');
            $table->integer('reply_count')->default(0)->comment('回复数');
            $table->smallInteger('is_top')->default(0)->comment('是否置顶');
            $table->smallInteger('is_excellent')->default(0)->comment('是否精选');
            $table->smallInteger('status')->default(1)->comment('状态：0待审核 1已通过 2已拒绝');
            $table->timestamp('create_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('update_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->smallInteger('del_flag')->default(0)->comment('删除标志');

            $table->index('course_id', 'idx_qa_course');
            $table->index('chapter_id', 'idx_qa_chapter');
            $table->index('member_id', 'idx_qa_member');
            $table->index('parent_id', 'idx_qa_parent');
            $table->index(['course_id', 'status', 'is_top'], 'idx_qa_list');
        });
        DB::statement("COMMENT ON TABLE app_course_qa IS '课程问答表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('app_course_qa');
        Schema::dropIfExists('app_member_learning_checkin');
        Schema::dropIfExists('app_member_learning_note');
    }
}
