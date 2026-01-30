<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateAppMemberCourseTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 用户课程表（已购买/领取的课程）
        Schema::create('app_member_course', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('ID');
            $table->bigInteger('member_id')->comment('用户ID');
            $table->bigInteger('course_id')->comment('课程ID');
            $table->string('order_no', 64)->nullable()->comment('订单号');
            $table->smallInteger('source_type')->default(1)->comment('来源：1购买 2免费领取 3兑换 4赠送 5活动');
            $table->decimal('paid_amount', 10, 2)->default(0)->comment('实付金额');
            $table->integer('paid_points')->default(0)->comment('使用积分');
            $table->timestamp('enroll_time')->nullable()->comment('报名时间');
            $table->timestamp('expire_time')->nullable()->comment('过期时间');
            $table->smallInteger('is_expired')->default(0)->comment('是否过期：0否 1是');
            
            // 学习进度
            $table->integer('learned_chapters')->default(0)->comment('已学章节数');
            $table->integer('total_chapters')->default(0)->comment('总章节数');
            $table->integer('learned_duration')->default(0)->comment('已学时长（秒）');
            $table->decimal('progress', 5, 2)->default(0)->comment('学习进度%');
            $table->bigInteger('last_chapter_id')->nullable()->comment('最后学习章节');
            $table->integer('last_position')->default(0)->comment('最后播放位置（秒）');
            $table->timestamp('last_learn_time')->nullable()->comment('最后学习时间');
            $table->smallInteger('is_completed')->default(0)->comment('是否完课：0否 1是');
            $table->timestamp('complete_time')->nullable()->comment('完课时间');
            
            // 打卡统计
            $table->integer('homework_submitted')->default(0)->comment('已提交作业数');
            $table->integer('homework_total')->default(0)->comment('总作业数');
            $table->integer('checkin_days')->default(0)->comment('打卡天数');
            
            $table->timestamp('create_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('update_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));

            $table->unique(['member_id', 'course_id'], 'uk_member_course');
            $table->index('member_id', 'idx_mc_member');
            $table->index('course_id', 'idx_mc_course');
            $table->index('order_no', 'idx_mc_order');
            $table->index(['member_id', 'is_expired', 'last_learn_time'], 'idx_mc_recent');
        });
        DB::statement("COMMENT ON TABLE app_member_course IS '用户课程表'");

        // 用户课表（章节解锁计划）
        Schema::create('app_member_schedule', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('ID');
            $table->bigInteger('member_id')->comment('用户ID');
            $table->bigInteger('course_id')->comment('课程ID');
            $table->bigInteger('chapter_id')->comment('章节ID');
            $table->bigInteger('member_course_id')->comment('用户课程ID');
            $table->date('schedule_date')->comment('计划日期');
            $table->time('schedule_time')->nullable()->comment('计划时间');
            $table->smallInteger('is_unlocked')->default(0)->comment('是否已解锁：0否 1是');
            $table->timestamp('unlock_time')->nullable()->comment('解锁时间');
            $table->smallInteger('is_learned')->default(0)->comment('是否已学习：0否 1是');
            $table->timestamp('learn_time')->nullable()->comment('学习时间');
            $table->smallInteger('is_notified')->default(0)->comment('是否已通知：0否 1是');
            $table->timestamp('create_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('update_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));

            $table->unique(['member_id', 'chapter_id'], 'uk_member_chapter');
            $table->index('member_id', 'idx_schedule_member');
            $table->index(['member_id', 'schedule_date'], 'idx_schedule_date');
            $table->index(['member_id', 'is_unlocked'], 'idx_schedule_unlock');
        });
        DB::statement("COMMENT ON TABLE app_member_schedule IS '用户课表（章节解锁计划）'");

        // 用户章节学习记录
        Schema::create('app_member_chapter_progress', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('ID');
            $table->bigInteger('member_id')->comment('用户ID');
            $table->bigInteger('course_id')->comment('课程ID');
            $table->bigInteger('chapter_id')->comment('章节ID');
            $table->integer('learned_duration')->default(0)->comment('已学时长（秒）');
            $table->integer('total_duration')->default(0)->comment('总时长（秒）');
            $table->decimal('progress', 5, 2)->default(0)->comment('进度%');
            $table->integer('last_position')->default(0)->comment('最后位置（秒）');
            $table->smallInteger('is_completed')->default(0)->comment('是否完成：0否 1是');
            $table->timestamp('complete_time')->nullable()->comment('完成时间');
            $table->integer('view_count')->default(0)->comment('观看次数');
            $table->timestamp('first_view_time')->nullable()->comment('首次观看时间');
            $table->timestamp('last_view_time')->nullable()->comment('最后观看时间');
            $table->timestamp('create_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('update_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));

            $table->unique(['member_id', 'chapter_id'], 'uk_member_chapter_progress');
            $table->index('member_id', 'idx_progress_member');
            $table->index(['member_id', 'course_id'], 'idx_progress_course');
        });
        DB::statement("COMMENT ON TABLE app_member_chapter_progress IS '用户章节学习进度表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('app_member_chapter_progress');
        Schema::dropIfExists('app_member_schedule');
        Schema::dropIfExists('app_member_course');
    }
}
