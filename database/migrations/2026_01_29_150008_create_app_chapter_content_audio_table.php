<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAppChapterContentAudioTable extends Migration
{
    public function up()
    {
        DB::statement("
            CREATE TABLE app_chapter_content_audio (
                id int8 NOT NULL GENERATED ALWAYS AS IDENTITY (INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 START 1 CACHE 1),
                chapter_id int8 NOT NULL,
                audio_url varchar(500) NULL,
                audio_id varchar(100) NULL,
                audio_source varchar(50) NOT NULL DEFAULT 'local',
                duration int4 NOT NULL DEFAULT 0,
                file_size int8 NOT NULL DEFAULT 0,
                cover_image varchar(500) NULL,
                transcript text NULL,
                timeline_text jsonb NOT NULL DEFAULT '[]',
                attachments jsonb NOT NULL DEFAULT '[]',
                allow_download int2 NOT NULL DEFAULT 0,
                background_play int2 NOT NULL DEFAULT 1,
                created_at timestamp(0) NULL,
                updated_at timestamp(0) NULL,
                deleted_at timestamp(0) NULL,
                PRIMARY KEY (id)
            )
        ");

        DB::statement("COMMENT ON COLUMN app_chapter_content_audio.id IS 'ID'");
        DB::statement("COMMENT ON COLUMN app_chapter_content_audio.chapter_id IS '章节ID'");
        DB::statement("COMMENT ON COLUMN app_chapter_content_audio.audio_url IS '音频地址'");
        DB::statement("COMMENT ON COLUMN app_chapter_content_audio.audio_id IS '音频ID（云存储）'");
        DB::statement("COMMENT ON COLUMN app_chapter_content_audio.audio_source IS '音频来源'");
        DB::statement("COMMENT ON COLUMN app_chapter_content_audio.duration IS '音频时长（秒）'");
        DB::statement("COMMENT ON COLUMN app_chapter_content_audio.file_size IS '文件大小（字节）'");
        DB::statement("COMMENT ON COLUMN app_chapter_content_audio.cover_image IS '音频封面'");
        DB::statement("COMMENT ON COLUMN app_chapter_content_audio.transcript IS '文字稿'");
        DB::statement("COMMENT ON COLUMN app_chapter_content_audio.timeline_text IS '时间轴文字'");
        DB::statement("COMMENT ON COLUMN app_chapter_content_audio.attachments IS '附件资料'");
        DB::statement("COMMENT ON COLUMN app_chapter_content_audio.allow_download IS '允许下载：0=否 1=是'");
        DB::statement("COMMENT ON COLUMN app_chapter_content_audio.background_play IS '允许后台播放：0=否 1=是'");

        DB::statement('CREATE UNIQUE INDEX uk_app_chapter_content_audio_chapter_id ON app_chapter_content_audio (chapter_id)');
        DB::statement("COMMENT ON TABLE app_chapter_content_audio IS '音频课章节内容表'");
    }

    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS app_chapter_content_audio');
    }
}
