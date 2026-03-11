<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAppActivityBaseTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("
            CREATE TABLE app_activity_base (
                activity_id int8 NOT NULL GENERATED ALWAYS AS IDENTITY (INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 START 1 CACHE 1),
                post_id int8 NOT NULL DEFAULT 0,
                title varchar(200) NOT NULL DEFAULT '',
                description text NOT NULL DEFAULT '',
                detail_html text NOT NULL DEFAULT '',
                cover jsonb NOT NULL DEFAULT '{}',
                banner jsonb NOT NULL DEFAULT '{}',
                rules_html text NOT NULL DEFAULT '',
                reward_desc text NOT NULL DEFAULT '',
                allowed_post_types jsonb NOT NULL DEFAULT '[1,2,3]',
                max_submissions int4 NOT NULL DEFAULT 0,
                start_time timestamp(0) NOT NULL,
                end_time timestamp(0) NOT NULL,
                sort_num int4 NOT NULL DEFAULT 0,
                status int2 NOT NULL DEFAULT 0,
                created_by int8 NOT NULL DEFAULT 0,
                created_at timestamp(0) NULL,
                updated_at timestamp(0) NULL,
                deleted_at timestamp(0) NULL,
                PRIMARY KEY (activity_id)
            )
        ");

        // 列注释
        DB::statement("COMMENT ON COLUMN app_activity_base.activity_id IS '活动ID'");
        DB::statement("COMMENT ON COLUMN app_activity_base.post_id IS '关联的信息流帖子ID（app_post_base.post_id，post_type=4）'");
        DB::statement("COMMENT ON COLUMN app_activity_base.title IS '活动标题'");
        DB::statement("COMMENT ON COLUMN app_activity_base.description IS '活动简介'");
        DB::statement("COMMENT ON COLUMN app_activity_base.detail_html IS '活动详情（富文本HTML）'");
        DB::statement("COMMENT ON COLUMN app_activity_base.cover IS '活动封面：{url, width, height}'");
        DB::statement("COMMENT ON COLUMN app_activity_base.banner IS '活动横幅图：{url, width, height}'");
        DB::statement("COMMENT ON COLUMN app_activity_base.rules_html IS '参与规则说明（富文本HTML）'");
        DB::statement("COMMENT ON COLUMN app_activity_base.reward_desc IS '奖励说明'");
        DB::statement("COMMENT ON COLUMN app_activity_base.allowed_post_types IS '允许的投稿类型：[1=图文,2=视频,3=文章]'");
        DB::statement("COMMENT ON COLUMN app_activity_base.max_submissions IS '每人最大投稿数，0=不限'");
        DB::statement("COMMENT ON COLUMN app_activity_base.start_time IS '活动开始时间'");
        DB::statement("COMMENT ON COLUMN app_activity_base.end_time IS '活动结束时间'");
        DB::statement("COMMENT ON COLUMN app_activity_base.sort_num IS '排序号（越大越靠前）'");
        DB::statement("COMMENT ON COLUMN app_activity_base.status IS '状态：0=草稿 1=已上线 2=已下线'");
        DB::statement("COMMENT ON COLUMN app_activity_base.created_by IS '创建人ID（管理员）'");

        // 索引
        DB::statement('CREATE INDEX idx_app_activity_base_status ON app_activity_base (status)');
        DB::statement('CREATE INDEX idx_app_activity_base_start_time ON app_activity_base (start_time)');
        DB::statement('CREATE INDEX idx_app_activity_base_end_time ON app_activity_base (end_time)');
        DB::statement('CREATE INDEX idx_app_activity_base_post_id ON app_activity_base (post_id)');
        DB::statement('CREATE INDEX idx_app_activity_base_sort_num ON app_activity_base (sort_num DESC)');

        // 表注释
        DB::statement("COMMENT ON TABLE app_activity_base IS '征稿活动主表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS app_activity_base');
    }
}
