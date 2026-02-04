<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAppMemberScheduleTable extends Migration
{
    public function up()
    {
        DB::statement("
            CREATE TABLE app_member_schedule (
                id int8 NOT NULL GENERATED ALWAYS AS IDENTITY (INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 START 1 CACHE 1),
                member_id int8 NOT NULL,
                course_id int8 NOT NULL,
                chapter_id int8 NOT NULL,
                member_course_id int8 NOT NULL,
                schedule_date date NOT NULL,
                schedule_time time NULL,
                is_unlocked int2 NOT NULL DEFAULT 0,
                unlock_time timestamp(0) NULL,
                is_learned int2 NOT NULL DEFAULT 0,
                learn_time timestamp(0) NULL,
                is_notified int2 NOT NULL DEFAULT 0,
                created_at timestamp(0) NULL,
                updated_at timestamp(0) NULL,
                deleted_at timestamp(0) NULL,
                PRIMARY KEY (id)
            )
        ");

        DB::statement("COMMENT ON COLUMN app_member_schedule.id IS 'ID'");
        DB::statement("COMMENT ON COLUMN app_member_schedule.member_id IS '用户ID'");
        DB::statement("COMMENT ON COLUMN app_member_schedule.course_id IS '课程ID'");
        DB::statement("COMMENT ON COLUMN app_member_schedule.chapter_id IS '章节ID'");
        DB::statement("COMMENT ON COLUMN app_member_schedule.member_course_id IS '用户课程ID'");
        DB::statement("COMMENT ON COLUMN app_member_schedule.schedule_date IS '计划日期'");
        DB::statement("COMMENT ON COLUMN app_member_schedule.schedule_time IS '计划时间'");
        DB::statement("COMMENT ON COLUMN app_member_schedule.is_unlocked IS '是否已解锁：0=否 1=是'");
        DB::statement("COMMENT ON COLUMN app_member_schedule.unlock_time IS '解锁时间'");
        DB::statement("COMMENT ON COLUMN app_member_schedule.is_learned IS '是否已学习：0=否 1=是'");
        DB::statement("COMMENT ON COLUMN app_member_schedule.learn_time IS '学习时间'");
        DB::statement("COMMENT ON COLUMN app_member_schedule.is_notified IS '是否已通知：0=否 1=是'");

        DB::statement('CREATE UNIQUE INDEX uk_app_member_schedule_member_chapter ON app_member_schedule (member_id, chapter_id)');
        DB::statement('CREATE INDEX idx_app_member_schedule_member_id ON app_member_schedule (member_id)');
        DB::statement('CREATE INDEX idx_app_member_schedule_date ON app_member_schedule (member_id, schedule_date)');
        DB::statement('CREATE INDEX idx_app_member_schedule_unlock ON app_member_schedule (member_id, is_unlocked)');
        DB::statement("COMMENT ON TABLE app_member_schedule IS '用户课表（章节解锁计划）'");
    }

    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS app_member_schedule');
    }
}
