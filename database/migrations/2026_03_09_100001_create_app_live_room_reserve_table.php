<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAppLiveRoomReserveTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("
            CREATE TABLE app_live_room_reserve (
                reserve_id int8 NOT NULL GENERATED ALWAYS AS IDENTITY (INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 START 1 CACHE 1),
                member_id int8 NOT NULL,
                room_id int8 NOT NULL,
                status int2 NOT NULL DEFAULT 1,
                created_at timestamp(0) NULL,
                updated_at timestamp(0) NULL,
                PRIMARY KEY (reserve_id)
            )
        ");

        DB::statement("COMMENT ON COLUMN app_live_room_reserve.reserve_id IS '预约记录ID'");
        DB::statement("COMMENT ON COLUMN app_live_room_reserve.member_id IS '会员ID'");
        DB::statement("COMMENT ON COLUMN app_live_room_reserve.room_id IS '直播间ID'");
        DB::statement("COMMENT ON COLUMN app_live_room_reserve.status IS '预约状态：1=正常预约 2=取消预约'");
        DB::statement("COMMENT ON COLUMN app_live_room_reserve.created_at IS '预约时间'");
        DB::statement("COMMENT ON COLUMN app_live_room_reserve.updated_at IS '更新时间'");

        DB::statement('CREATE UNIQUE INDEX uk_app_live_room_reserve_member_room ON app_live_room_reserve (member_id, room_id)');
        DB::statement('CREATE INDEX idx_app_live_room_reserve_room_status ON app_live_room_reserve (room_id, status)');
        DB::statement('CREATE INDEX idx_app_live_room_reserve_member_status ON app_live_room_reserve (member_id, status)');

        DB::statement("COMMENT ON TABLE app_live_room_reserve IS '直播间预约记录表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS app_live_room_reserve');
    }
}
