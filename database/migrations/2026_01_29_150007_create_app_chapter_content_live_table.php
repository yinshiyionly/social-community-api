<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAppChapterContentLiveTable extends Migration
{
    public function up()
    {
        DB::statement("
            CREATE TABLE app_chapter_content_live (
                id int8 NOT NULL GENERATED ALWAYS AS IDENTITY (INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 START 1 CACHE 1),
                chapter_id int8 NOT NULL,
                live_platform varchar(50) NOT NULL DEFAULT 'custom',
                live_room_id varchar(100) NULL,
                live_push_url varchar(500) NULL,
                live_pull_url varchar(500) NULL,
                live_cover varchar(500) NULL,
                live_start_time timestamp(0) NULL,
                live_end_time timestamp(0) NULL,
                live_duration int4 NOT NULL DEFAULT 0,
                live_status int2 NOT NULL DEFAULT 0,
                has_playback int2 NOT NULL DEFAULT 0,
                playback_url varchar(500) NULL,
                playback_duration int4 NOT NULL DEFAULT 0,
                allow_chat int2 NOT NULL DEFAULT 1,
                allow_gift int2 NOT NULL DEFAULT 0,
                online_count int4 NOT NULL DEFAULT 0,
                max_online_count int4 NOT NULL DEFAULT 0,
                attachments jsonb NOT NULL DEFAULT '[]',
                create_time timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP,
                update_time timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            )
        ");

        DB::statement("COMMENT ON COLUMN app_chapter_content_live.id IS 'ID'");
        DB::statement("COMMENT ON COLUMN app_chapter_content_live.chapter_id IS '章节ID'");
        DB::statement("COMMENT ON COLUMN app_chapter_content_live.live_platform IS '直播平台：custom/aliyun/tencent/agora'");
        DB::statement("COMMENT ON COLUMN app_chapter_content_live.live_room_id IS '直播间ID'");
        DB::statement("COMMENT ON COLUMN app_chapter_content_live.live_push_url IS '推流地址'");
        DB::statement("COMMENT ON COLUMN app_chapter_content_live.live_pull_url IS '拉流地址'");
        DB::statement("COMMENT ON COLUMN app_chapter_content_live.live_cover IS '直播封面'");
        DB::statement("COMMENT ON COLUMN app_chapter_content_live.live_start_time IS '直播开始时间'");
        DB::statement("COMMENT ON COLUMN app_chapter_content_live.live_end_time IS '直播结束时间'");
        DB::statement("COMMENT ON COLUMN app_chapter_content_live.live_duration IS '预计时长（分钟）'");
        DB::statement("COMMENT ON COLUMN app_chapter_content_live.live_status IS '直播状态：0=未开始 1=直播中 2=已结束 3=已取消'");
        DB::statement("COMMENT ON COLUMN app_chapter_content_live.has_playback IS '是否有回放：0=否 1=是'");
        DB::statement("COMMENT ON COLUMN app_chapter_content_live.playback_url IS '回放地址'");
        DB::statement("COMMENT ON COLUMN app_chapter_content_live.playback_duration IS '回放时长（秒）'");
        DB::statement("COMMENT ON COLUMN app_chapter_content_live.allow_chat IS '允许聊天：0=否 1=是'");
        DB::statement("COMMENT ON COLUMN app_chapter_content_live.allow_gift IS '允许送礼：0=否 1=是'");
        DB::statement("COMMENT ON COLUMN app_chapter_content_live.online_count IS '在线人数'");
        DB::statement("COMMENT ON COLUMN app_chapter_content_live.max_online_count IS '最高在线'");
        DB::statement("COMMENT ON COLUMN app_chapter_content_live.attachments IS '直播资料'");

        DB::statement('CREATE UNIQUE INDEX uk_app_chapter_content_live_chapter_id ON app_chapter_content_live (chapter_id)');
        DB::statement('CREATE INDEX idx_app_chapter_content_live_start_time ON app_chapter_content_live (live_start_time)');
        DB::statement('CREATE INDEX idx_app_chapter_content_live_status ON app_chapter_content_live (live_status)');
        DB::statement("COMMENT ON TABLE app_chapter_content_live IS '直播课章节内容表'");
    }

    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS app_chapter_content_live');
    }
}
