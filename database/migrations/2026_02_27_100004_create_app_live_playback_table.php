<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAppLivePlaybackTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("
            CREATE TABLE app_live_playback (
                playback_id int8 NOT NULL GENERATED ALWAYS AS IDENTITY (INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 START 1 CACHE 1),
                room_id int8 NOT NULL,
                playback_title varchar(200) NOT NULL DEFAULT '',
                playback_url varchar(500) NULL,
                playback_cover varchar(500) NULL,
                playback_duration int4 NOT NULL DEFAULT 0,
                file_size int8 NOT NULL DEFAULT 0,
                source_type int2 NOT NULL DEFAULT 1,
                sort_order int4 NOT NULL DEFAULT 0,
                view_count int4 NOT NULL DEFAULT 0,
                status int2 NOT NULL DEFAULT 1,
                created_at timestamp(0) NULL,
                updated_at timestamp(0) NULL,
                deleted_at timestamp(0) NULL,
                PRIMARY KEY (playback_id)
            )
        ");

        DB::statement("COMMENT ON COLUMN app_live_playback.playback_id IS '回放ID'");
        DB::statement("COMMENT ON COLUMN app_live_playback.room_id IS '直播间ID'");
        DB::statement("COMMENT ON COLUMN app_live_playback.playback_title IS '回放标题'");
        DB::statement("COMMENT ON COLUMN app_live_playback.playback_url IS '回放地址'");
        DB::statement("COMMENT ON COLUMN app_live_playback.playback_cover IS '回放封面'");
        DB::statement("COMMENT ON COLUMN app_live_playback.playback_duration IS '回放时长（秒）'");
        DB::statement("COMMENT ON COLUMN app_live_playback.file_size IS '文件大小（字节）'");
        DB::statement("COMMENT ON COLUMN app_live_playback.source_type IS '来源类型：1=自动录制 2=手动上传'");
        DB::statement("COMMENT ON COLUMN app_live_playback.sort_order IS '排序'");
        DB::statement("COMMENT ON COLUMN app_live_playback.view_count IS '观看次数'");
        DB::statement("COMMENT ON COLUMN app_live_playback.status IS '状态：0=禁用 1=启用'");

        // 索引
        DB::statement('CREATE INDEX idx_app_live_playback_room_id ON app_live_playback (room_id, sort_order)');
        DB::statement('CREATE INDEX idx_app_live_playback_status ON app_live_playback (status)');

        DB::statement("COMMENT ON TABLE app_live_playback IS '直播回放表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS app_live_playback');
    }
}
