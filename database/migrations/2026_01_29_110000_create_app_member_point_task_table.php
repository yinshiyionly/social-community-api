<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAppMemberPointTaskTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("
            CREATE TABLE app_member_point_task (
                task_id int4 NOT NULL GENERATED ALWAYS AS IDENTITY (INCREMENT 1 MINVALUE 1 MAXVALUE 2147483647 START 1 CACHE 1),
                task_code varchar(50) NOT NULL DEFAULT '',
                task_name varchar(100) NOT NULL DEFAULT '',
                task_type int2 NOT NULL DEFAULT 1,
                task_category varchar(50) NULL,
                point_value int4 NOT NULL DEFAULT 0,
                daily_limit int4 NOT NULL DEFAULT 1,
                total_limit int4 NOT NULL DEFAULT 0,
                icon varchar(255) NULL,
                description varchar(500) NULL,
                jump_url varchar(255) NULL,
                sort_order int4 NOT NULL DEFAULT 0,
                status int2 NOT NULL DEFAULT 1,
                start_time timestamp(0) NULL,
                end_time timestamp(0) NULL,
                created_at timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP,
                deleted_at timestamp(0) NULL,
                PRIMARY KEY (task_id)
            )
        ");

        // 列注释
        DB::statement("COMMENT ON COLUMN app_member_point_task.task_id IS '任务ID'");
        DB::statement("COMMENT ON COLUMN app_member_point_task.task_code IS '任务编码'");
        DB::statement("COMMENT ON COLUMN app_member_point_task.task_name IS '任务名称'");
        DB::statement("COMMENT ON COLUMN app_member_point_task.task_type IS '任务类型：1日常任务 2成长任务 3特殊任务'");
        DB::statement("COMMENT ON COLUMN app_member_point_task.task_category IS '任务分类：daily/growth/activity'");
        DB::statement("COMMENT ON COLUMN app_member_point_task.point_value IS '奖励积分值'");
        DB::statement("COMMENT ON COLUMN app_member_point_task.daily_limit IS '每日完成次数上限'");
        DB::statement("COMMENT ON COLUMN app_member_point_task.total_limit IS '总完成次数上限'");
        DB::statement("COMMENT ON COLUMN app_member_point_task.icon IS '任务图标'");
        DB::statement("COMMENT ON COLUMN app_member_point_task.description IS '任务描述'");
        DB::statement("COMMENT ON COLUMN app_member_point_task.jump_url IS '跳转链接'");
        DB::statement("COMMENT ON COLUMN app_member_point_task.sort_order IS '排序'");
        DB::statement("COMMENT ON COLUMN app_member_point_task.status IS '状态：1启用 2禁用'");
        DB::statement("COMMENT ON COLUMN app_member_point_task.start_time IS '生效开始时间'");
        DB::statement("COMMENT ON COLUMN app_member_point_task.end_time IS '生效结束时间'");
        DB::statement("COMMENT ON COLUMN app_member_point_task.created_at IS '创建时间'");
        DB::statement("COMMENT ON COLUMN app_member_point_task.updated_at IS '更新时间'");
        DB::statement("COMMENT ON COLUMN app_member_point_task.deleted_at IS '删除时间'");

        // 索引
        DB::statement('CREATE UNIQUE INDEX uk_app_member_point_task_task_code ON app_member_point_task (task_code) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX idx_app_member_point_task_task_type ON app_member_point_task (task_type)');
        DB::statement('CREATE INDEX idx_app_member_point_task_status ON app_member_point_task (status)');

        // 表注释
        DB::statement("COMMENT ON TABLE app_member_point_task IS '用户积分任务配置表'");

        // 初始化任务配置
        DB::table('app_member_point_task')->insert([
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
        DB::statement('DROP TABLE IF EXISTS app_member_point_task');
    }
}
