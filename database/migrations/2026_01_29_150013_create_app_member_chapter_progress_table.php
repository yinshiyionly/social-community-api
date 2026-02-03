<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAppMemberChapterProgressTable extends Migration
{
    public function up()
    {
        DB::statement("
            CREATE TABLE app_member_chapter_progress (
                id int8 NOT NULL GENERATED ALWAYS AS IDENTITY (INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 START 1 CACHE 1),
                member_id int8 NOT NULL,
                course_id int8 NOT NULL,
                chapter_id int8 NOT NULL,
                learned_duration int4 NOT NULL DEFAULT 0,
                total_duration int4 NOT NULL DEFAULT 0,
                progress numeric(5,2) NOT NULL DEFAULT 0,
                last_position int4 NOT NULL DEFAULT 0,
                is_completed int2 NOT NULL DEFAULT 0,
                complete_time timestamp(0) NULL,
                view_count int4 NOT NULL DEFAULT 0,
                first_view_time timestamp(0) NULL,
                last_view_time timestamp(0) NULL,
                create_time timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP,
                update_time timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            )
        ");

        DB::statement("COMMENT ON COLUMN app_member_chapter_progress.id IS 'ID'");
        DB::statement("COMMENT ON COLUMN app_member_chapter_progress.member_id IS '用户ID'");
        DB::statement("COMMENT ON COLUMN app_member_chapter_progress.course_id IS '课程ID'");
        DB::statement("COMMENT ON COLUMN app_member_chapter_progress.chapter_id IS '章节ID'");
        DB::statement("COMMENT ON COLUMN app_member_chapter_progress.learned_duration IS '已学时长（秒）'");
        DB::statement("COMMENT ON COLUMN app_member_chapter_progress.total_duration IS '总时长（秒）'");
        DB::statement("COMMENT ON COLUMN app_member_chapter_progress.progress IS '进度%'");
        DB::statement("COMMENT ON COLUMN app_member_chapter_progress.last_position IS '最后位置（秒）'");
        DB::statement("COMMENT ON COLUMN app_member_chapter_progress.is_completed IS '是否完成：0=否 1=是'");
        DB::statement("COMMENT ON COLUMN app_member_chapter_progress.complete_time IS '完成时间'");
        DB::statement("COMMENT ON COLUMN app_member_chapter_progress.view_count IS '观看次数'");
        DB::statement("COMMENT ON COLUMN app_member_chapter_progress.first_view_time IS '首次观看时间'");
        DB::statement("COMMENT ON COLUMN app_member_chapter_progress.last_view_time IS '最后观看时间'");

        DB::statement('CREATE UNIQUE INDEX uk_app_member_chapter_progress_member_chapter ON app_member_chapter_progress (member_id, chapter_id)');
        DB::statement('CREATE INDEX idx_app_member_chapter_progress_member_id ON app_member_chapter_progress (member_id)');
        DB::statement('CREATE INDEX idx_app_member_chapter_progress_course ON app_member_chapter_progress (member_id, course_id)');
        DB::statement("COMMENT ON TABLE app_member_chapter_progress IS '用户章节学习进度表'");
    }

    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS app_member_chapter_progress');
    }
}
