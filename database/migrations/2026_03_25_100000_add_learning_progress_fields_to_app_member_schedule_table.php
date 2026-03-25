<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddLearningProgressFieldsToAppMemberScheduleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE app_member_schedule ADD COLUMN learned_duration int4 NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE app_member_schedule ADD COLUMN total_duration int4 NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE app_member_schedule ADD COLUMN progress numeric(5,2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE app_member_schedule ADD COLUMN last_position int4 NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE app_member_schedule ADD COLUMN is_completed int2 NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE app_member_schedule ADD COLUMN complete_time timestamp(0) NULL');
        DB::statement('ALTER TABLE app_member_schedule ADD COLUMN view_count int4 NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE app_member_schedule ADD COLUMN first_view_time timestamp(0) NULL');
        DB::statement('ALTER TABLE app_member_schedule ADD COLUMN last_view_time timestamp(0) NULL');

        DB::statement("COMMENT ON COLUMN app_member_schedule.learned_duration IS '已学时长（秒）'");
        DB::statement("COMMENT ON COLUMN app_member_schedule.total_duration IS '总时长（秒）'");
        DB::statement("COMMENT ON COLUMN app_member_schedule.progress IS '进度%'");
        DB::statement("COMMENT ON COLUMN app_member_schedule.last_position IS '最后位置（秒）'");
        DB::statement("COMMENT ON COLUMN app_member_schedule.is_completed IS '是否完成：0=否 1=是'");
        DB::statement("COMMENT ON COLUMN app_member_schedule.complete_time IS '完成时间'");
        DB::statement("COMMENT ON COLUMN app_member_schedule.view_count IS '观看次数'");
        DB::statement("COMMENT ON COLUMN app_member_schedule.first_view_time IS '首次观看时间'");
        DB::statement("COMMENT ON COLUMN app_member_schedule.last_view_time IS '最后观看时间'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE app_member_schedule DROP COLUMN IF EXISTS last_view_time');
        DB::statement('ALTER TABLE app_member_schedule DROP COLUMN IF EXISTS first_view_time');
        DB::statement('ALTER TABLE app_member_schedule DROP COLUMN IF EXISTS view_count');
        DB::statement('ALTER TABLE app_member_schedule DROP COLUMN IF EXISTS complete_time');
        DB::statement('ALTER TABLE app_member_schedule DROP COLUMN IF EXISTS is_completed');
        DB::statement('ALTER TABLE app_member_schedule DROP COLUMN IF EXISTS last_position');
        DB::statement('ALTER TABLE app_member_schedule DROP COLUMN IF EXISTS progress');
        DB::statement('ALTER TABLE app_member_schedule DROP COLUMN IF EXISTS total_duration');
        DB::statement('ALTER TABLE app_member_schedule DROP COLUMN IF EXISTS learned_duration');
    }
}
