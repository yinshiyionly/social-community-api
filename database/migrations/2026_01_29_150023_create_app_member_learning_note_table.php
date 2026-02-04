<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAppMemberLearningNoteTable extends Migration
{
    public function up()
    {
        DB::statement("
            CREATE TABLE app_member_learning_note (
                note_id int8 NOT NULL GENERATED ALWAYS AS IDENTITY (INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 START 1 CACHE 1),
                member_id int8 NOT NULL,
                course_id int8 NOT NULL,
                chapter_id int8 NOT NULL,
                time_point int4 NOT NULL DEFAULT 0,
                content text NULL,
                images jsonb NOT NULL DEFAULT '[]',
                is_public int2 NOT NULL DEFAULT 0,
                like_count int4 NOT NULL DEFAULT 0,
                created_at timestamp(0) NULL,
                updated_at timestamp(0) NULL,
                deleted_at timestamp(0) NULL,
                PRIMARY KEY (note_id)
            )
        ");

        DB::statement("COMMENT ON COLUMN app_member_learning_note.note_id IS '笔记ID'");
        DB::statement("COMMENT ON COLUMN app_member_learning_note.member_id IS '用户ID'");
        DB::statement("COMMENT ON COLUMN app_member_learning_note.course_id IS '课程ID'");
        DB::statement("COMMENT ON COLUMN app_member_learning_note.chapter_id IS '章节ID'");
        DB::statement("COMMENT ON COLUMN app_member_learning_note.time_point IS '时间点（秒）'");
        DB::statement("COMMENT ON COLUMN app_member_learning_note.content IS '笔记内容'");
        DB::statement("COMMENT ON COLUMN app_member_learning_note.images IS '笔记图片'");
        DB::statement("COMMENT ON COLUMN app_member_learning_note.is_public IS '是否公开：0=私密 1=公开'");
        DB::statement("COMMENT ON COLUMN app_member_learning_note.like_count IS '点赞数'");

        DB::statement('CREATE INDEX idx_app_member_learning_note_member_id ON app_member_learning_note (member_id)');
        DB::statement('CREATE INDEX idx_app_member_learning_note_course ON app_member_learning_note (member_id, course_id)');
        DB::statement('CREATE INDEX idx_app_member_learning_note_chapter ON app_member_learning_note (member_id, chapter_id)');
        DB::statement("COMMENT ON TABLE app_member_learning_note IS '学习笔记表'");
    }

    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS app_member_learning_note');
    }
}
