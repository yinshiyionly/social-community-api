<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateAppCourseTeacherTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('app_course_teacher', function (Blueprint $table) {
            $table->bigIncrements('teacher_id')->comment('讲师ID');
            $table->bigInteger('member_id')->nullable()->comment('关联用户ID');
            $table->string('teacher_name', 50)->comment('讲师姓名');
            $table->string('avatar', 500)->nullable()->comment('头像');
            $table->string('title', 100)->nullable()->comment('头衔/职称');
            $table->string('brief', 500)->nullable()->comment('简介');
            $table->text('description')->nullable()->comment('详细介绍');
            $table->jsonb('tags')->default('[]')->comment('标签');
            $table->jsonb('certificates')->default('[]')->comment('资质证书');
            $table->integer('course_count')->default(0)->comment('课程数');
            $table->integer('student_count')->default(0)->comment('学员数');
            $table->decimal('avg_rating', 2, 1)->default(5.0)->comment('平均评分');
            $table->integer('sort_order')->default(0)->comment('排序');
            $table->smallInteger('is_recommend')->default(0)->comment('是否推荐');
            $table->smallInteger('status')->default(1)->comment('状态：1启用 2禁用');
            $table->string('create_by', 64)->nullable();
            $table->timestamp('create_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->string('update_by', 64)->nullable();
            $table->timestamp('update_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->smallInteger('del_flag')->default(0)->comment('删除标志');

            $table->index('member_id', 'idx_teacher_member');
            $table->index('status', 'idx_teacher_status');
        });

        DB::statement("COMMENT ON TABLE app_course_teacher IS '课程讲师表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('app_course_teacher');
    }
}
