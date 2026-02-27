<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAppLiveViewerLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("
            CREATE TABLE app_live_viewer_log (
                id int8 NOT NULL GENERATED ALWAYS AS IDENTITY (INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 START 1 CACHE 1),
                room_id int8 NOT NULL,
                member_id int8 NOT NULL,
                join_time timestamp(0) NOT NULL,
                leave_time timestamp(0) NULL,
                watch_duration int4 NOT NULL DEFAULT 0,
                device_type varchar(50) NOT NULL DEFAULT '',
                ip_address varchar(50) NULL,
                created_at timestamp(0) NULL,
                PRIMARY KEY (id)
            )
        ");

        DB::statement("COMMENT ON COLUMN app_live_viewer_log.id IS 'ID'");
        DB::statement("COMMENT ON COLUMN app_live_viewer_log.room_id IS '直播间ID'");
        DB::statement("COMMENT ON COLUMN app_live_viewer_log.member_id IS '观看者ID'");
        DB::statement("COMMENT ON COLUMN app_live_viewer_log.join_time IS '进入时间'");
        DB::statement("COMMENT ON COLUMN app_live_viewer_log.leave_time IS '离开时间'");
        DB::statement("COMMENT ON COLUMN app_live_viewer_log.watch_duration IS '观看时长（秒）'");
        DB::statement("COMMENT ON COLUMN app_live_viewer_log.device_type IS '设备类型：ios/android/pc/h5'");
        DB::statement("COMMENT ON COLUMN app_live_viewer_log.ip_address IS 'IP地址'");

        // 索引
        DB::statement('CREATE INDEX idx_app_live_viewer_log_room_id ON app_live_viewer_log (room_id, join_time)');
        DB::statement('CREATE INDEX idx_app_live_viewer_log_member_id ON app_live_viewer_log (member_id)');
        DB::statement('CREATE UNIQUE INDEX uk_app_live_viewer_log_room_member_join ON app_live_viewer_log (room_id, member_id, join_time)');

        DB::statement("COMMENT ON TABLE app_live_viewer_log IS '直播间观看记录表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS app_live_viewer_log');
    }
}
