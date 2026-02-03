<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAppChapterHomeworkTable extends Migration
{
    public function up()
    {
        DB::statement("
            CREATE TABLE app_chapter_homework (
                homework_id int8 NOT NULL GENERATED ALWAYS AS IDENTITY (INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 START 1 CACHE 1),
                chapter_id int8 NOT NULL,
                course_id int8 NOT NULL,
                homework_title varchar(200) NOT NULL DEFAULT '',
                homework_content text NULL,
                homework_type int2 NOT NULL DEFAULT 1,
                homework_config jsonb NOT NULL DEFAULT '{}',
                point_reward int4 NOT NULL DEFAULT 0,
                deadline_days int4 NOT NULL DEFAULT 0,
                need_review int2 NOT NULL DEFAULT 0,
                show_others int2 NOT NULL DEFAULT 1,
                submit_count int4 NOT NULL DEFAULT 0,
                sort_order int4 NOT NULL DEFAULT 0,
                status int2 NOT NULL DEFAULT 1,
                create_time timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP,
                update_time timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (homework_id)
            )
        ");

        DB::statement("COMMENT ON COLUMN app_chapter_homework.homework_id IS '作业ID'");
        DB::statement("COMMENT ON COLUMN app_chapter_homework.chapter_id IS '章节ID'");
        DB::statement("COMMENT ON COLUMN app_chapter_homework.course_id IS '课程ID'");
        DB::statement("COMMENT ON COLUMN app_chapter_homework.homework_title IS '作业标题'");
        DB::statement("COMMENT ON COLUMN app_chapter_homework.homework_content IS '作业要求'");
        DB::statement("COMMENT ON COLUMN app_chapter_homework.homework_type IS '作业类型：1=图文打卡 2=视频打卡 3=问答 4=文件提交'");
        DB::statement("COMMENT ON COLUMN app_chapter_homework.homework_config IS '作业配置'");
        DB::statement("COMMENT ON COLUMN app_chapter_homework.point_reward IS '完成奖励积分'");
        DB::statement("COMMENT ON COLUMN app_chapter_homework.deadline_days IS '截止天数（0=不限）'");
        DB::statement("COMMENT ON COLUMN app_chapter_homework.need_review IS '是否需要批改：0=否 1=是'");
        DB::statement("COMMENT ON COLUMN app_chapter_homework.show_others IS '是否展示他人作业：0=否 1=是'");
        DB::statement("COMMENT ON COLUMN app_chapter_homework.submit_count IS '提交数'");
        DB::statement("COMMENT ON COLUMN app_chapter_homework.sort_order IS '排序'");
        DB::statement("COMMENT ON COLUMN app_chapter_homework.status IS '状态：1=启用 2=禁用'");

        DB::statement('CREATE INDEX idx_app_chapter_homework_chapter_id ON app_chapter_homework (chapter_id)');
        DB::statement('CREATE INDEX idx_app_chapter_homework_course_id ON app_chapter_homework (course_id)');
        DB::statement("COMMENT ON TABLE app_chapter_homework IS '章节作业配置表'");
    }

    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS app_chapter_homework');
    }
}
