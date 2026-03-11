<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAppActivityStatTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("
            CREATE TABLE app_activity_stat (
                activity_id int8 NOT NULL,
                view_count int4 NOT NULL DEFAULT 0,
                submission_count int4 NOT NULL DEFAULT 0,
                participant_count int4 NOT NULL DEFAULT 0,
                share_count int4 NOT NULL DEFAULT 0,
                created_at timestamp(0) NULL,
                updated_at timestamp(0) NULL,
                PRIMARY KEY (activity_id)
            )
        ");

        // 列注释
        DB::statement("COMMENT ON COLUMN app_activity_stat.activity_id IS '活动ID'");
        DB::statement("COMMENT ON COLUMN app_activity_stat.view_count IS '浏览数'");
        DB::statement("COMMENT ON COLUMN app_activity_stat.submission_count IS '投稿数'");
        DB::statement("COMMENT ON COLUMN app_activity_stat.participant_count IS '参与人数'");
        DB::statement("COMMENT ON COLUMN app_activity_stat.share_count IS '分享数'");

        // 索引
        DB::statement('CREATE INDEX idx_app_activity_stat_submission_count ON app_activity_stat (submission_count)');

        // 表注释
        DB::statement("COMMENT ON TABLE app_activity_stat IS '征稿活动统计表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS app_activity_stat');
    }
}
