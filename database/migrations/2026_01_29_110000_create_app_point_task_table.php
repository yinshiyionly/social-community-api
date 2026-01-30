<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateAppPointTaskTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('app_point_task', function (Blueprint $table) {
            $table->increments('task_id')->comment('任务ID');
            $table->string('task_code', 50)->comment('任务编码');
            $table->string('task_name', 100)->comment('任务名称');
            $table->smallInteger('task_type')->default(1)->comment('任务类型：1日常任务 2成长任务 3特殊任务');
            $table->string('task_category', 50)->nullable()->comment('任务分类：daily/growth/activity');
            $table->integer('point_value')->default(0)->comment('奖励积分值');
            $table->integer('daily_limit')->default(1)->comment('每日完成次数上限');
            $table->integer('total_limit')->default(0)->comment('总完成次数上限');
            $table->string('icon', 255)->nullable()->comment('任务图标');
            $table->string('description', 500)->nullable()->comment('任务描述');
            $table->string('jump_url', 255)->nullable()->comment('跳转链接');
            $table->integer('sort_order')->default(0)->comment('排序');
            $table->smallInteger('status')->default(1)->comment('状态：1启用 2禁用');
            $table->timestamp('start_time')->nullable()->comment('生效开始时间');
            $table->timestamp('end_time')->nullable()->comment('生效结束时间');
            $table->string('create_by', 64)->nullable();
            $table->timestamp('create_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->string('update_by', 64)->nullable();
            $table->timestamp('update_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->smallInteger('del_flag')->default(0)->comment('删除标志：0正常 1删除');

            $table->unique(['task_code', 'del_flag'], 'uk_task_code');
            $table->index('task_type', 'idx_point_task_type');
            $table->index('task_category', 'idx_point_task_category');
            $table->index('status', 'idx_point_task_status');
        });

        DB::statement("COMMENT ON TABLE app_point_task IS '积分任务配置表'");

        // 初始化任务配置
        DB::table('app_point_task')->insert([
            // 日常任务
            ['task_code' => 'daily_checkin', 'task_name' => '每日签到', 'task_type' => 1, 'task_category' => 'daily', 'point_value' => 10, 'daily_limit' => 1, 'total_limit' => 0, 'description' => '每日签到获得积分', 'sort_order' => 1],
            ['task_code' => 'daily_post', 'task_name' => '每日发帖', 'task_type' => 1, 'task_category' => 'daily', 'point_value' => 20, 'daily_limit' => 3, 'total_limit' => 0, 'description' => '每日发布帖子，最多3次', 'sort_order' => 2],
            ['task_code' => 'daily_comment', 'task_name' => '每日评论', 'task_type' => 1, 'task_category' => 'daily', 'point_value' => 5, 'daily_limit' => 5, 'total_limit' => 0, 'description' => '每日评论帖子，最多5次', 'sort_order' => 3],
            ['task_code' => 'daily_like', 'task_name' => '每日点赞', 'task_type' => 1, 'task_category' => 'daily', 'point_value' => 2, 'daily_limit' => 10, 'total_limit' => 0, 'description' => '每日点赞帖子，最多10次', 'sort_order' => 4],
            ['task_code' => 'daily_share', 'task_name' => '每日分享', 'task_type' => 1, 'task_category' => 'daily', 'point_value' => 10, 'daily_limit' => 3, 'total_limit' => 0, 'description' => '每日分享帖子，最多3次', 'sort_order' => 5],
            // 成长任务
            ['task_code' => 'first_post', 'task_name' => '首次发帖', 'task_type' => 2, 'task_category' => 'growth', 'point_value' => 50, 'daily_limit' => 0, 'total_limit' => 1, 'description' => '首次发布帖子奖励', 'sort_order' => 10],
            ['task_code' => 'first_follow', 'task_name' => '首次关注', 'task_type' => 2, 'task_category' => 'growth', 'point_value' => 20, 'daily_limit' => 0, 'total_limit' => 1, 'description' => '首次关注其他用户', 'sort_order' => 11],
            ['task_code' => 'first_purchase', 'task_name' => '首次购买课程', 'task_type' => 2, 'task_category' => 'growth', 'point_value' => 100, 'daily_limit' => 0, 'total_limit' => 1, 'description' => '首次购买课程奖励', 'sort_order' => 12],
            ['task_code' => 'first_avatar', 'task_name' => '完善头像', 'task_type' => 2, 'task_category' => 'growth', 'point_value' => 30, 'daily_limit' => 0, 'total_limit' => 1, 'description' => '首次上传头像', 'sort_order' => 13],
            ['task_code' => 'first_bio', 'task_name' => '完善简介', 'task_type' => 2, 'task_category' => 'growth', 'point_value' => 20, 'daily_limit' => 0, 'total_limit' => 1, 'description' => '首次填写个人简介', 'sort_order' => 14],
            ['task_code' => 'invite_user', 'task_name' => '邀请好友', 'task_type' => 2, 'task_category' => 'growth', 'point_value' => 50, 'daily_limit' => 0, 'total_limit' => 0, 'description' => '每邀请一位好友注册', 'sort_order' => 15],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('app_point_task');
    }
}
