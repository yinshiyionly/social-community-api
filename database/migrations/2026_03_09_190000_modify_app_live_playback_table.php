<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class ModifyAppLivePlaybackTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 按约定重建表结构，旧表数据不保留。
        DB::statement('DROP TABLE IF EXISTS app_live_playback');

        DB::statement("
            CREATE TABLE app_live_playback (
                id int8 NOT NULL GENERATED ALWAYS AS IDENTITY (INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 START 1 CACHE 1),
                playback_id int8 NOT NULL,
                room_id int8 NULL,
                third_party_room_id varchar(50) NOT NULL DEFAULT '',
                session_id int4 NOT NULL DEFAULT 0,
                video_id int8 NOT NULL DEFAULT 0,
                name varchar(255) NOT NULL DEFAULT '',
                status int2 NOT NULL DEFAULT 10,
                create_time timestamp(0) NOT NULL,
                length int4 NOT NULL DEFAULT 0,
                total_transcode_size int8 NOT NULL DEFAULT 0,
                play_times int4 NOT NULL DEFAULT 0,
                play_url varchar(512) NOT NULL DEFAULT '',
                preface_url varchar(512) NULL,
                publish_status int2 NOT NULL DEFAULT 1,
                version int4 NOT NULL DEFAULT 0,
                created_at timestamp(0) NULL,
                updated_at timestamp(0) NULL,
                deleted_at timestamp(0) NULL,
                PRIMARY KEY (id)
            )
        ");

        DB::statement("COMMENT ON COLUMN app_live_playback.id IS '自增主键ID'");
        DB::statement("COMMENT ON COLUMN app_live_playback.playback_id IS '当前回放的唯一id'");
        DB::statement("COMMENT ON COLUMN app_live_playback.room_id IS '本地直播间ID（app_live_room.room_id）'");
        DB::statement("COMMENT ON COLUMN app_live_playback.third_party_room_id IS '百家云回放教室号'");
        DB::statement("COMMENT ON COLUMN app_live_playback.session_id IS '回放序列号（只针对长期房间生成的多段回放有用）'");
        DB::statement("COMMENT ON COLUMN app_live_playback.video_id IS '回放对应的视频的ID'");
        DB::statement("COMMENT ON COLUMN app_live_playback.name IS '回放名称'");
        DB::statement("COMMENT ON COLUMN app_live_playback.status IS '回放视频状态：10=生成中 20=转码中 30=转码失败 100=转码成功'");
        DB::statement("COMMENT ON COLUMN app_live_playback.create_time IS '回放生成时间'");
        DB::statement("COMMENT ON COLUMN app_live_playback.length IS '回放视频时长（秒）'");
        DB::statement("COMMENT ON COLUMN app_live_playback.total_transcode_size IS '回放视频总大小（含原文件和转码文件，单位字节）'");
        DB::statement("COMMENT ON COLUMN app_live_playback.play_times IS '回放观看次数'");
        DB::statement("COMMENT ON COLUMN app_live_playback.play_url IS '回放WEB端观看地址'");
        DB::statement("COMMENT ON COLUMN app_live_playback.preface_url IS '回放视频封面地址'");
        DB::statement("COMMENT ON COLUMN app_live_playback.publish_status IS '视频屏蔽状态：1=未屏蔽 2=已屏蔽'");
        DB::statement("COMMENT ON COLUMN app_live_playback.version IS '裁剪版本，未裁剪为0'");
        DB::statement("COMMENT ON COLUMN app_live_playback.created_at IS '创建时间'");
        DB::statement("COMMENT ON COLUMN app_live_playback.updated_at IS '更新时间'");
        DB::statement("COMMENT ON COLUMN app_live_playback.deleted_at IS '删除时间'");

        DB::statement('CREATE UNIQUE INDEX uk_app_live_playback_playback_id ON app_live_playback (playback_id)');
        DB::statement('CREATE INDEX idx_app_live_playback_room_id ON app_live_playback (room_id)');
        DB::statement('CREATE INDEX idx_app_live_playback_third_party_room_id ON app_live_playback (third_party_room_id)');
        DB::statement('CREATE INDEX idx_app_live_playback_status ON app_live_playback (status)');
        DB::statement('CREATE INDEX idx_app_live_playback_publish_status ON app_live_playback (publish_status)');
        DB::statement('CREATE INDEX idx_app_live_playback_create_time ON app_live_playback (create_time)');

        DB::statement("COMMENT ON TABLE app_live_playback IS '百家云直播回放表'");
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
