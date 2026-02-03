<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAppChapterContentVideoTable extends Migration
{
    public function up()
    {
        DB::statement("
            CREATE TABLE app_chapter_content_video (
                id int8 NOT NULL GENERATED ALWAYS AS IDENTITY (INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 START 1 CACHE 1),
                chapter_id int8 NOT NULL,
                video_url varchar(500) NULL,
                video_id varchar(100) NULL,
                video_source varchar(50) NOT NULL DEFAULT 'local',
                duration int4 NOT NULL DEFAULT 0,
                width int4 NOT NULL DEFAULT 0,
                height int4 NOT NULL DEFAULT 0,
                file_size int8 NOT NULL DEFAULT 0,
                cover_image varchar(500) NULL,
                quality_list jsonb NOT NULL DEFAULT '[]',
                subtitles jsonb NOT NULL DEFAULT '[]',
                attachments jsonb NOT NULL DEFAULT '[]',
                allow_download int2 NOT NULL DEFAULT 0,
                drm_enabled int2 NOT NULL DEFAULT 0,
                create_time timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP,
                update_time timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            )
        ");

        DB::statement("COMMENT ON COLUMN app_chapter_content_video.id IS 'ID'");
        DB::statement("COMMENT ON COLUMN app_chapter_content_video.chapter_id IS '章节ID'");
        DB::statement("COMMENT ON COLUMN app_chapter_content_video.video_url IS '视频地址'");
        DB::statement("COMMENT ON COLUMN app_chapter_content_video.video_id IS '视频ID（云存储）'");
        DB::statement("COMMENT ON COLUMN app_chapter_content_video.video_source IS '视频来源：local/aliyun/tencent/volcengine'");
        DB::statement("COMMENT ON COLUMN app_chapter_content_video.duration IS '视频时长（秒）'");
        DB::statement("COMMENT ON COLUMN app_chapter_content_video.width IS '视频宽度'");
        DB::statement("COMMENT ON COLUMN app_chapter_content_video.height IS '视频高度'");
        DB::statement("COMMENT ON COLUMN app_chapter_content_video.file_size IS '文件大小（字节）'");
        DB::statement("COMMENT ON COLUMN app_chapter_content_video.cover_image IS '视频封面'");
        DB::statement("COMMENT ON COLUMN app_chapter_content_video.quality_list IS '清晰度列表'");
        DB::statement("COMMENT ON COLUMN app_chapter_content_video.subtitles IS '字幕列表'");
        DB::statement("COMMENT ON COLUMN app_chapter_content_video.attachments IS '课件附件'");
        DB::statement("COMMENT ON COLUMN app_chapter_content_video.allow_download IS '允许下载：0=否 1=是'");
        DB::statement("COMMENT ON COLUMN app_chapter_content_video.drm_enabled IS 'DRM加密：0=否 1=是'");

        DB::statement('CREATE UNIQUE INDEX uk_app_chapter_content_video_chapter_id ON app_chapter_content_video (chapter_id)');
        DB::statement("COMMENT ON TABLE app_chapter_content_video IS '录播课章节内容表'");
    }

    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS app_chapter_content_video');
    }
}
