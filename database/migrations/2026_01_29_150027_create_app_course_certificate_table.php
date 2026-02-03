<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAppCourseCertificateTable extends Migration
{
    public function up()
    {
        DB::statement("
            CREATE TABLE app_course_certificate (
                id int8 NOT NULL GENERATED ALWAYS AS IDENTITY (INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 START 1 CACHE 1),
                course_id int8 NOT NULL,
                template_id int8 NOT NULL,
                certificate_title varchar(200) NOT NULL DEFAULT '',
                certificate_content text NULL,
                issue_condition int2 NOT NULL DEFAULT 1,
                min_progress numeric(5,2) NOT NULL DEFAULT 100,
                min_homework int4 NOT NULL DEFAULT 0,
                status int2 NOT NULL DEFAULT 1,
                create_time timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP,
                update_time timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            )
        ");

        DB::statement("COMMENT ON COLUMN app_course_certificate.id IS 'ID'");
        DB::statement("COMMENT ON COLUMN app_course_certificate.course_id IS '课程ID'");
        DB::statement("COMMENT ON COLUMN app_course_certificate.template_id IS '证书模板ID'");
        DB::statement("COMMENT ON COLUMN app_course_certificate.certificate_title IS '证书标题'");
        DB::statement("COMMENT ON COLUMN app_course_certificate.certificate_content IS '证书内容'");
        DB::statement("COMMENT ON COLUMN app_course_certificate.issue_condition IS '发放条件：1=完课 2=完课+作业 3=手动发放'");
        DB::statement("COMMENT ON COLUMN app_course_certificate.min_progress IS '最低完课进度%'");
        DB::statement("COMMENT ON COLUMN app_course_certificate.min_homework IS '最低作业完成数'");
        DB::statement("COMMENT ON COLUMN app_course_certificate.status IS '状态：1=启用 2=禁用'");

        DB::statement('CREATE UNIQUE INDEX uk_app_course_certificate_course_id ON app_course_certificate (course_id)');
        DB::statement("COMMENT ON TABLE app_course_certificate IS '课程证书配置表'");
    }

    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS app_course_certificate');
    }
}
