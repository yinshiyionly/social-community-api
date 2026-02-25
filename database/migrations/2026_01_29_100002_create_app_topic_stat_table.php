<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAppTopicStatTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("
            CREATE TABLE app_topic_stat (
                topic_id int8 NOT NULL,
                post_count int4 NOT NULL DEFAULT 0,
                view_count int4 NOT NULL DEFAULT 0,
                follow_count int4 NOT NULL DEFAULT 0,
                participant_count int4 NOT NULL DEFAULT 0,
                today_post_count int4 NOT NULL DEFAULT 0,
                heat_score numeric(12,4) NOT NULL DEFAULT 0,
                last_post_at timestamp(0) NULL,
                created_at timestamp(0) NULL,
                updated_at timestamp(0) NULL,
                PRIMARY KEY (topic_id)
            )
        ");

        // 列注释
        DB::statement("COMMENT ON COLUMN app_topic_stat.topic_id IS '话题ID'");
        DB::statement("COMMENT ON COLUMN app_topic_stat.post_count IS '帖子数'");
        DB::statement("COMMENT ON COLUMN app_topic_stat.view_count IS '浏览数'");
        DB::statement("COMMENT ON COLUMN app_topic_stat.follow_count IS '关注数'");
        DB::statement("COMMENT ON COLUMN app_topic_stat.participant_count IS '参与人数'");
        DB::statement("COMMENT ON COLUMN app_topic_stat.today_post_count IS '今日新增帖子数'");
        DB::statement("COMMENT ON COLUMN app_topic_stat.heat_score IS '热度分'");
        DB::statement("COMMENT ON COLUMN app_topic_stat.last_post_at IS '最后发帖时间'");
        DB::statement("COMMENT ON COLUMN app_topic_stat.created_at IS '创建时间'");
        DB::statement("COMMENT ON COLUMN app_topic_stat.updated_at IS '更新时间'");

        // 索引
        DB::statement('CREATE INDEX idx_app_topic_stat_heat_score ON app_topic_stat (heat_score)');
        DB::statement('CREATE INDEX idx_app_topic_stat_post_count ON app_topic_stat (post_count)');

        // 表注释
        DB::statement("COMMENT ON TABLE app_topic_stat IS '话题统计表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS app_topic_stat');
    }
}
