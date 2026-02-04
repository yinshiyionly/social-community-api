<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAppMemberCertificateTable extends Migration
{
    public function up()
    {
        DB::statement("
            CREATE TABLE app_member_certificate (
                cert_id int8 NOT NULL GENERATED ALWAYS AS IDENTITY (INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 START 1 CACHE 1),
                cert_no varchar(64) NOT NULL,
                member_id int8 NOT NULL,
                course_id int8 NOT NULL,
                template_id int8 NOT NULL,
                member_name varchar(50) NOT NULL DEFAULT '',
                course_title varchar(200) NOT NULL DEFAULT '',
                cert_image varchar(500) NULL,
                final_progress numeric(5,2) NOT NULL DEFAULT 0,
                final_homework int4 NOT NULL DEFAULT 0,
                issue_time timestamp(0) NULL,
                status int2 NOT NULL DEFAULT 1,
                created_at timestamp(0) NULL,
                PRIMARY KEY (cert_id)
            )
        ");

        DB::statement("COMMENT ON COLUMN app_member_certificate.cert_id IS '证书ID'");
        DB::statement("COMMENT ON COLUMN app_member_certificate.cert_no IS '证书编号'");
        DB::statement("COMMENT ON COLUMN app_member_certificate.member_id IS '用户ID'");
        DB::statement("COMMENT ON COLUMN app_member_certificate.course_id IS '课程ID'");
        DB::statement("COMMENT ON COLUMN app_member_certificate.template_id IS '模板ID'");
        DB::statement("COMMENT ON COLUMN app_member_certificate.member_name IS '用户姓名'");
        DB::statement("COMMENT ON COLUMN app_member_certificate.course_title IS '课程名称'");
        DB::statement("COMMENT ON COLUMN app_member_certificate.cert_image IS '证书图片'");
        DB::statement("COMMENT ON COLUMN app_member_certificate.final_progress IS '最终进度'");
        DB::statement("COMMENT ON COLUMN app_member_certificate.final_homework IS '完成作业数'");
        DB::statement("COMMENT ON COLUMN app_member_certificate.issue_time IS '发放时间'");
        DB::statement("COMMENT ON COLUMN app_member_certificate.status IS '状态：1=有效 2=已撤销'");

        DB::statement('CREATE UNIQUE INDEX uk_app_member_certificate_cert_no ON app_member_certificate (cert_no)');
        DB::statement('CREATE UNIQUE INDEX uk_app_member_certificate_member_course ON app_member_certificate (member_id, course_id)');
        DB::statement('CREATE INDEX idx_app_member_certificate_member_id ON app_member_certificate (member_id)');
        DB::statement('CREATE INDEX idx_app_member_certificate_course_id ON app_member_certificate (course_id)');
        DB::statement("COMMENT ON TABLE app_member_certificate IS '用户证书表'");
    }

    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS app_member_certificate');
    }
}
