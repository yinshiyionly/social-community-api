<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAppCourseChapterTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("
            CREATE TABLE app_course_chapter (
                chapter_id int8 NOT NULL GENERATED ALWAYS AS IDENTITY (INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 START 1 CACHE 1),
                course_id int8 NOT NULL,
                chapter_no int4 NOT NULL DEFAULT 0,
                chapter_title varchar(200) NOT NULL DEFAULT '',
                chapter_subtitle varchar(300) NULL,
                cover_image varchar(500) NULL,
                brief text NULL,
                is_free int2 NOT NULL DEFAULT 0,
                is_preview int2 NOT NULL DEFAULT 0,
                unlock_type int2 NOT NULL DEFAULT 1,
                unlock_days int4 NOT NULL DEFAULT 0,
                unlock_date date NULL,
                unlock_time time NULL,
                has_homework int2 NOT NULL DEFAULT 0,
                homework_required int2 NOT NULL DEFAULT 0,
                duration int4 NOT NULL DEFAULT 0,
                min_learn_time int4 NOT NULL DEFAULT 0,
                allow_skip int2 NOT NULL DEFAULT 0,
                allow_speed int2 NOT NULL DEFAULT 1,
                view_count int4 NOT NULL DEFAULT 0,
                complete_count int4 NOT NULL DEFAULT 0,
                homework_count int4 NOT NULL DEFAULT 0,
                sort_order int4 NOT NULL DEFAULT 0,
                status int2 NOT NULL DEFAULT 1,
                create_by varchar(64) NULL,
                create_time timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP,
                update_by varchar(64) NULL,
                update_time timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP,
                del_flag int2 NOT NULL DEFAULT 0,
                PRIMARY KEY (chapter_id)
            )
        ");

        // 列注释
        DB::statement("COMMENT ON COLUMN app_course_chapter.chapter_id IS '章节ID'");
        DB::statement("COMMENT ON COLUMN app_course_chapter.course_id IS '课程ID'");
        DB::statement("COMMENT ON COLUMN app_course_chapter.chapter_no IS '章节序号'");
        DB::statement("COMMENT ON COLUMN app_course_chapter.chapter_title IS '章节标题'");
        DB::statement("COMMENT ON COLUMN app_course_chapter.chapter_subtitle IS '章节副标题'");
        DB::statement("COMMENT ON COLUMN app_course_chapter.cover_image IS '章节封面'");
        DB::statement("COMMENT ON COLUMN app_course_chapter.brief IS '章节简介'");
        DB::statement("COMMENT ON COLUMN app_course_chapter.is_free IS '是否免费试看：0=否 1=是'");
        DB::statement("COMMENT ON COLUMN app_course_chapter.is_preview IS '是否先导课：0=否 1=是'");
        DB::statement("COMMENT ON COLUMN app_course_chapter.unlock_type IS '解锁类型：1=立即解锁 2=按天数解锁 3=按日期解锁'");
        DB::statement("COMMENT ON COLUMN app_course_chapter.unlock_days IS '解锁天数（相对于领取/购买日期）'");
        DB::statement("COMMENT ON COLUMN app_course_chapter.unlock_date IS '固定解锁日期'");
        DB::statement("COMMENT ON COLUMN app_course_chapter.unlock_time IS '解锁时间点'");
        DB::statement("COMMENT ON COLUMN app_course_chapter.has_homework IS '是否有作业：0=否 1=是'");
        DB::statement("COMMENT ON COLUMN app_course_chapter.homework_required IS '作业是否必做：0=否 1=是'");
        DB::statement("COMMENT ON COLUMN app_course_chapter.duration IS '时长（秒）'");
        DB::statement("COMMENT ON COLUMN app_course_chapter.min_learn_time IS '最少学习时长（秒）'");
        DB::statement("COMMENT ON COLUMN app_course_chapter.allow_skip IS '允许跳过：0=否 1=是'");
        DB::statement("COMMENT ON COLUMN app_course_chapter.allow_speed IS '允许倍速：0=否 1=是'");
        DB::statement("COMMENT ON COLUMN app_course_chapter.view_count IS '观看次数'");
        DB::statement("COMMENT ON COLUMN app_course_chapter.complete_count IS '完成人数'");
        DB::statement("COMMENT ON COLUMN app_course_chapter.homework_count IS '作业提交数'");
        DB::statement("COMMENT ON COLUMN app_course_chapter.sort_order IS '排序'");
        DB::statement("COMMENT ON COLUMN app_course_chapter.status IS '状态：0=草稿 1=上架 2=下架'");
        DB::statement("COMMENT ON COLUMN app_course_chapter.del_flag IS '删除标志：0=正常 1=删除'");

        // 索引
        DB::statement('CREATE INDEX idx_app_course_chapter_course_id ON app_course_chapter (course_id)');
        DB::statement('CREATE INDEX idx_app_course_chapter_course_no ON app_course_chapter (course_id, chapter_no)');
        DB::statement('CREATE INDEX idx_app_course_chapter_list ON app_course_chapter (course_id, status, sort_order)');

        // 表注释
        DB::statement("COMMENT ON TABLE app_course_chapter IS '课程章节基础表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS app_course_chapter');
    }
}
