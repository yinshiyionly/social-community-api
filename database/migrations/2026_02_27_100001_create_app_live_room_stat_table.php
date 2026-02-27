<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAppLiveRoomStatTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("
            CREATE TABLE app_live_room_stat (
                room_id int8 NOT NULL,
                total_viewer_count int4 NOT NULL DEFAULT 0,
                max_online_count int4 NOT NULL DEFAULT 0,
                current_online_count int4 NOT NULL DEFAULT 0,
                like_count int4 NOT NULL DEFAULT 0,
                message_count int4 NOT NULL DEFAULT 0,
                gift_count int4 NOT NULL DEFAULT 0,
                gift_amount numeric(12,2) NOT NULL DEFAULT 0,
                share_count int4 NOT NULL DEFAULT 0,
                avg_watch_duration int4 NOT NULL DEFAULT 0,
                created_at timestamp(0) NULL,
                updated_at timestamp(0) NULL,
                PRIMARY KEY (room_id)
            )
        ");

        DB::statement("COMMENT ON COLUMN app_live_room_stat.room_id IS '直播间ID'");
        DB::statement("COMMENT ON COLUMN app_live_room_stat.total_viewer_count IS '累计观看人数'");
        DB::statement("COMMENT ON COLUMN app_live_room_stat.max_online_count IS '最高同时在线人数'");
        DB::statement("COMMENT ON COLUMN app_live_room_stat.current_online_count IS '当前在线人数'");
        DB::statement("COMMENT ON COLUMN app_live_room_stat.like_count IS '点赞数'");
        DB::statement("COMMENT ON COLUMN app_live_room_stat.message_count IS '聊天消息数'");
        DB::statement("COMMENT ON COLUMN app_live_room_stat.gift_count IS '礼物数量'");
        DB::statement("COMMENT ON COLUMN app_live_room_stat.gift_amount IS '礼物总金额'");
        DB::statement("COMMENT ON COLUMN app_live_room_stat.share_count IS '分享次数'");
        DB::statement("COMMENT ON COLUMN app_live_room_stat.avg_watch_duration IS '平均观看时长（秒）'");

        DB::statement("COMMENT ON TABLE app_live_room_stat IS '直播间数据统计表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS app_live_room_stat');
    }
}
