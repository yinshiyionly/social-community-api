<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAppMemberTaskRecordTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("
            CREATE TABLE app_member_task_record (
                record_id int8 NOT NULL GENERATED ALWAYS AS IDENTITY (INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 START 1 CACHE 1),
                member_id int8 NOT NULL,
                task_id int4 NOT NULL,
                task_code varchar(50) NOT NULL DEFAULT '',
                task_type int2 NOT NULL,
                point_value int4 NOT NULL DEFAULT 0,
                complete_date date NOT NULL,
                complete_count int4 NOT NULL DEFAULT 1,
                biz_id varchar(64) NULL,
                created_at timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP,
                deleted_at timestamp(0) NULL,
                PRIMARY KEY (record_id)
            )
        ");

        // 列注释
        DB::statement("COMMENT ON COLUMN app_member_task_record.record_id IS '记录ID'");
        DB::statement("COMMENT ON COLUMN app_member_task_record.member_id IS '用户ID'");
        DB::statement("COMMENT ON COLUMN app_member_task_record.task_id IS '任务ID'");
        DB::statement("COMMENT ON COLUMN app_member_task_record.task_code IS '任务编码'");
        DB::statement("COMMENT ON COLUMN app_member_task_record.task_type IS '任务类型'");
        DB::statement("COMMENT ON COLUMN app_member_task_record.point_value IS '获得积分'");
        DB::statement("COMMENT ON COLUMN app_member_task_record.complete_date IS '完成日期'");
        DB::statement("COMMENT ON COLUMN app_member_task_record.complete_count IS '当日完成次数'");
        DB::statement("COMMENT ON COLUMN app_member_task_record.biz_id IS '业务ID'");
        DB::statement("COMMENT ON COLUMN app_member_task_record.created_at IS '创建时间'");
        DB::statement("COMMENT ON COLUMN app_member_task_record.updated_at IS '更新时间'");
        DB::statement("COMMENT ON COLUMN app_member_task_record.deleted_at IS '删除时间'");

        // 索引
        DB::statement('CREATE UNIQUE INDEX uk_app_member_task_record_member_task_date_biz ON app_member_task_record (member_id, task_code, complete_date, biz_id)');
        DB::statement('CREATE INDEX idx_app_member_task_record_member_id ON app_member_task_record (member_id)');
        DB::statement('CREATE INDEX idx_app_member_task_record_task_code ON app_member_task_record (task_code)');
        DB::statement('CREATE INDEX idx_app_member_task_record_complete_date ON app_member_task_record (complete_date)');
        DB::statement('CREATE INDEX idx_app_member_task_record_member_task_date ON app_member_task_record (member_id, task_code, complete_date)');

        // 表注释
        DB::statement("COMMENT ON TABLE app_member_task_record IS '用户任务完成记录表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS app_member_task_record');
    }
}
