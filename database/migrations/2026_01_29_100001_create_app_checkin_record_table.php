<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAppCheckinRecordTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("
            CREATE TABLE app_checkin_record (
                record_id int8 NOT NULL GENERATED ALWAYS AS IDENTITY (INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 START 1 CACHE 1),
                member_id int8 NOT NULL,
                checkin_date date NOT NULL,
                continuous_days int4 NOT NULL DEFAULT 1,
                reward_type int2 NOT NULL DEFAULT 1,
                reward_value int4 NOT NULL DEFAULT 0,
                extra_reward_value int4 NOT NULL DEFAULT 0,
                checkin_time timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP,
                client_ip varchar(50) NULL,
                device_info varchar(200) NULL,
                create_time timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (record_id)
            )
        ");

        DB::statement("COMMENT ON COLUMN app_checkin_record.record_id IS '记录ID'");
        DB::statement("COMMENT ON COLUMN app_checkin_record.member_id IS '用户ID'");
        DB::statement("COMMENT ON COLUMN app_checkin_record.checkin_date IS '签到日期'");
        DB::statement("COMMENT ON COLUMN app_checkin_record.continuous_days IS '当次连续签到天数'");
        DB::statement("COMMENT ON COLUMN app_checkin_record.reward_type IS '获得奖励类型'");
        DB::statement("COMMENT ON COLUMN app_checkin_record.reward_value IS '获得奖励数值'");
        DB::statement("COMMENT ON COLUMN app_checkin_record.extra_reward_value IS '获得额外奖励'");
        DB::statement("COMMENT ON COLUMN app_checkin_record.checkin_time IS '签到时间'");
        DB::statement("COMMENT ON COLUMN app_checkin_record.client_ip IS '签到IP'");
        DB::statement("COMMENT ON COLUMN app_checkin_record.device_info IS '设备信息'");
        DB::statement("COMMENT ON COLUMN app_checkin_record.create_time IS '创建时间'");

        DB::statement('CREATE UNIQUE INDEX uk_app_checkin_record_member_id_checkin_date ON app_checkin_record (member_id, checkin_date)');
        DB::statement('CREATE INDEX idx_app_checkin_record_member_id ON app_checkin_record (member_id)');
        DB::statement('CREATE INDEX idx_app_checkin_record_checkin_date ON app_checkin_record (checkin_date)');

        DB::statement("COMMENT ON TABLE app_checkin_record IS '用户签到记录表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS app_checkin_record');
    }
}
