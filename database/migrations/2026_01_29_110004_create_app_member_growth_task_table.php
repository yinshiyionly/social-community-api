<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAppMemberGrowthTaskTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("
            CREATE TABLE app_member_growth_task (
                id int8 NOT NULL GENERATED ALWAYS AS IDENTITY (INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 START 1 CACHE 1),
                member_id int8 NOT NULL,
                task_id int4 NOT NULL,
                task_code varchar(50) NOT NULL DEFAULT '',
                is_completed int2 NOT NULL DEFAULT 0,
                complete_time timestamp(0) NULL,
                point_value int4 NOT NULL DEFAULT 0,
                created_at timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP,
                deleted_at timestamp(0) NULL,
                PRIMARY KEY (id)
            )
        ");

        // 列注释
        DB::statement("COMMENT ON COLUMN app_member_growth_task.id IS 'ID'");
        DB::statement("COMMENT ON COLUMN app_member_growth_task.member_id IS '用户ID'");
        DB::statement("COMMENT ON COLUMN app_member_growth_task.task_id IS '任务ID'");
        DB::statement("COMMENT ON COLUMN app_member_growth_task.task_code IS '任务编码'");
        DB::statement("COMMENT ON COLUMN app_member_growth_task.is_completed IS '是否完成：0未完成 1已完成'");
        DB::statement("COMMENT ON COLUMN app_member_growth_task.complete_time IS '完成时间'");
        DB::statement("COMMENT ON COLUMN app_member_growth_task.point_value IS '获得积分'");
        DB::statement("COMMENT ON COLUMN app_member_growth_task.created_at IS '创建时间'");
        DB::statement("COMMENT ON COLUMN app_member_growth_task.updated_at IS '更新时间'");
        DB::statement("COMMENT ON COLUMN app_member_growth_task.deleted_at IS '删除时间'");

        // 索引
        DB::statement('CREATE UNIQUE INDEX uk_app_member_growth_task_member_task ON app_member_growth_task (member_id, task_code) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX idx_app_member_growth_task_member_id ON app_member_growth_task (member_id)');
        DB::statement('CREATE INDEX idx_app_member_growth_task_task_code ON app_member_growth_task (task_code)');

        // 表注释
        DB::statement("COMMENT ON TABLE app_member_growth_task IS '用户成长任务状态表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS app_member_growth_task');
    }
}
