<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAppCourseViewLogTable extends Migration
{
    public function up()
    {
        DB::statement("
            CREATE TABLE app_course_view_log (
                id int8 NOT NULL GENERATED ALWAYS AS IDENTITY (INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 START 1 CACHE 1),
                member_id int8 NULL,
                course_id int8 NOT NULL,
                device_id varchar(100) NULL,
                client_ip varchar(50) NULL,
                user_agent varchar(500) NULL,
                referer varchar(500) NULL,
                duration int4 NOT NULL DEFAULT 0,
                created_at timestamp(0) NULL,
                PRIMARY KEY (id)
            )
        ");

        DB::statement("COMMENT ON COLUMN app_course_view_log.id IS 'ID'");
        DB::statement("COMMENT ON COLUMN app_course_view_log.member_id IS '用户ID'");
        DB::statement("COMMENT ON COLUMN app_course_view_log.course_id IS '课程ID'");
        DB::statement("COMMENT ON COLUMN app_course_view_log.device_id IS '设备ID'");
        DB::statement("COMMENT ON COLUMN app_course_view_log.referer IS '来源页'");
        DB::statement("COMMENT ON COLUMN app_course_view_log.duration IS '停留时长（秒）'");

        DB::statement('CREATE INDEX idx_app_course_view_log_member_id ON app_course_view_log (member_id)');
        DB::statement('CREATE INDEX idx_app_course_view_log_course_id ON app_course_view_log (course_id)');
        DB::statement('CREATE INDEX idx_app_course_view_log_created_at ON app_course_view_log (created_at)');
        DB::statement("COMMENT ON TABLE app_course_view_log IS '课程浏览记录表'");
    }

    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS app_course_view_log');
    }
}
