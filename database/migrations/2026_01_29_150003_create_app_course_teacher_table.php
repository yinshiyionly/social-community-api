<?php

use Illuminate\Database\Migrations\Migration;
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
        DB::statement("
            CREATE TABLE app_course_teacher (
                teacher_id int8 NOT NULL GENERATED ALWAYS AS IDENTITY (INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 START 1 CACHE 1),
                member_id int8 NULL,
                teacher_name varchar(50) NOT NULL DEFAULT '',
                avatar varchar(500) NULL,
                title varchar(100) NULL,
                brief varchar(500) NULL,
                description text NULL,
                tags jsonb NOT NULL DEFAULT '[]',
                certificates jsonb NOT NULL DEFAULT '[]',
                course_count int4 NOT NULL DEFAULT 0,
                student_count int4 NOT NULL DEFAULT 0,
                avg_rating numeric(2,1) NOT NULL DEFAULT 5.0,
                sort_order int4 NOT NULL DEFAULT 0,
                is_recommend int2 NOT NULL DEFAULT 0,
                status int2 NOT NULL DEFAULT 1,
                create_by varchar(64) NULL,
                create_time timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP,
                update_by varchar(64) NULL,
                update_time timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP,
                del_flag int2 NOT NULL DEFAULT 0,
                PRIMARY KEY (teacher_id)
            )
        ");

        // 列注释
        DB::statement("COMMENT ON COLUMN app_course_teacher.teacher_id IS '讲师ID'");
        DB::statement("COMMENT ON COLUMN app_course_teacher.member_id IS '关联用户ID'");
        DB::statement("COMMENT ON COLUMN app_course_teacher.teacher_name IS '讲师姓名'");
        DB::statement("COMMENT ON COLUMN app_course_teacher.avatar IS '头像'");
        DB::statement("COMMENT ON COLUMN app_course_teacher.title IS '头衔/职称'");
        DB::statement("COMMENT ON COLUMN app_course_teacher.brief IS '简介'");
        DB::statement("COMMENT ON COLUMN app_course_teacher.description IS '详细介绍'");
        DB::statement("COMMENT ON COLUMN app_course_teacher.tags IS '标签'");
        DB::statement("COMMENT ON COLUMN app_course_teacher.certificates IS '资质证书'");
        DB::statement("COMMENT ON COLUMN app_course_teacher.course_count IS '课程数'");
        DB::statement("COMMENT ON COLUMN app_course_teacher.student_count IS '学员数'");
        DB::statement("COMMENT ON COLUMN app_course_teacher.avg_rating IS '平均评分'");
        DB::statement("COMMENT ON COLUMN app_course_teacher.sort_order IS '排序'");
        DB::statement("COMMENT ON COLUMN app_course_teacher.is_recommend IS '是否推荐：0=否 1=是'");
        DB::statement("COMMENT ON COLUMN app_course_teacher.status IS '状态：1=启用 2=禁用'");
        DB::statement("COMMENT ON COLUMN app_course_teacher.del_flag IS '删除标志：0=正常 1=删除'");

        // 索引
        DB::statement('CREATE INDEX idx_app_course_teacher_member_id ON app_course_teacher (member_id)');
        DB::statement('CREATE INDEX idx_app_course_teacher_status ON app_course_teacher (status)');

        // 表注释
        DB::statement("COMMENT ON TABLE app_course_teacher IS '课程讲师表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS app_course_teacher');
    }
}
