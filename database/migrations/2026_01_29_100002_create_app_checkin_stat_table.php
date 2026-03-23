<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAppCheckinStatTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("
            CREATE TABLE app_checkin_stat (
                stat_id int8 NOT NULL GENERATED ALWAYS AS IDENTITY (INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 START 1 CACHE 1),
                member_id int8 NOT NULL,
                total_checkin_days int4 NOT NULL DEFAULT 0,
                continuous_days int4 NOT NULL DEFAULT 0,
                max_continuous_days int4 NOT NULL DEFAULT 0,
                total_reward_value int8 NOT NULL DEFAULT 0,
                last_checkin_date date NULL,
                last_checkin_time timestamp(0) NULL,
                create_time timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP,
                update_time timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (stat_id)
            )
        ");

        DB::statement("COMMENT ON COLUMN app_checkin_stat.stat_id IS '统计ID'");
        DB::statement("COMMENT ON COLUMN app_checkin_stat.member_id IS '用户ID'");
        DB::statement("COMMENT ON COLUMN app_checkin_stat.total_checkin_days IS '累计签到天数'");
        DB::statement("COMMENT ON COLUMN app_checkin_stat.continuous_days IS '当前连续签到天数'");
        DB::statement("COMMENT ON COLUMN app_checkin_stat.max_continuous_days IS '历史最大连续签到天数'");
        DB::statement("COMMENT ON COLUMN app_checkin_stat.total_reward_value IS '累计获得奖励'");
        DB::statement("COMMENT ON COLUMN app_checkin_stat.last_checkin_date IS '最后签到日期'");
        DB::statement("COMMENT ON COLUMN app_checkin_stat.last_checkin_time IS '最后签到时间'");
        DB::statement("COMMENT ON COLUMN app_checkin_stat.create_time IS '创建时间'");
        DB::statement("COMMENT ON COLUMN app_checkin_stat.update_time IS '更新时间'");

        DB::statement('CREATE UNIQUE INDEX uk_app_checkin_stat_member_id ON app_checkin_stat (member_id)');
        DB::statement('CREATE INDEX idx_app_checkin_stat_last_checkin_date ON app_checkin_stat (last_checkin_date)');

        DB::statement("COMMENT ON TABLE app_checkin_stat IS '用户签到统计表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS app_checkin_stat');
    }
}
