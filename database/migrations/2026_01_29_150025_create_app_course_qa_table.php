<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAppCourseQaTable extends Migration
{
    public function up()
    {
        DB::statement("
            CREATE TABLE app_course_qa (
                qa_id int8 NOT NULL GENERATED ALWAYS AS IDENTITY (INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 START 1 CACHE 1),
                course_id int8 NOT NULL,
                chapter_id int8 NULL,
                member_id int8 NOT NULL,
                parent_id int8 NOT NULL DEFAULT 0,
                content text NOT NULL,
                images jsonb NOT NULL DEFAULT '[]',
                is_teacher_reply int2 NOT NULL DEFAULT 0,
                like_count int4 NOT NULL DEFAULT 0,
                reply_count int4 NOT NULL DEFAULT 0,
                is_top int2 NOT NULL DEFAULT 0,
                is_excellent int2 NOT NULL DEFAULT 0,
                status int2 NOT NULL DEFAULT 1,
                create_time timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP,
                update_time timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP,
                del_flag int2 NOT NULL DEFAULT 0,
                PRIMARY KEY (qa_id)
            )
        ");

        DB::statement("COMMENT ON COLUMN app_course_qa.qa_id IS '问答ID'");
        DB::statement("COMMENT ON COLUMN app_course_qa.course_id IS '课程ID'");
        DB::statement("COMMENT ON COLUMN app_course_qa.chapter_id IS '章节ID'");
        DB::statement("COMMENT ON COLUMN app_course_qa.member_id IS '提问用户ID'");
        DB::statement("COMMENT ON COLUMN app_course_qa.parent_id IS '父级ID（回复时）'");
        DB::statement("COMMENT ON COLUMN app_course_qa.content IS '内容'");
        DB::statement("COMMENT ON COLUMN app_course_qa.images IS '图片'");
        DB::statement("COMMENT ON COLUMN app_course_qa.is_teacher_reply IS '是否讲师回复：0=否 1=是'");
        DB::statement("COMMENT ON COLUMN app_course_qa.like_count IS '点赞数'");
        DB::statement("COMMENT ON COLUMN app_course_qa.reply_count IS '回复数'");
        DB::statement("COMMENT ON COLUMN app_course_qa.is_top IS '是否置顶：0=否 1=是'");
        DB::statement("COMMENT ON COLUMN app_course_qa.is_excellent IS '是否精选：0=否 1=是'");
        DB::statement("COMMENT ON COLUMN app_course_qa.status IS '状态：0=待审核 1=已通过 2=已拒绝'");
        DB::statement("COMMENT ON COLUMN app_course_qa.del_flag IS '删除标志：0=正常 1=删除'");

        DB::statement('CREATE INDEX idx_app_course_qa_course_id ON app_course_qa (course_id)');
        DB::statement('CREATE INDEX idx_app_course_qa_chapter_id ON app_course_qa (chapter_id)');
        DB::statement('CREATE INDEX idx_app_course_qa_member_id ON app_course_qa (member_id)');
        DB::statement('CREATE INDEX idx_app_course_qa_parent_id ON app_course_qa (parent_id)');
        DB::statement('CREATE INDEX idx_app_course_qa_list ON app_course_qa (course_id, status, is_top)');
        DB::statement("COMMENT ON TABLE app_course_qa IS '课程问答表'");
    }

    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS app_course_qa');
    }
}
