<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAppTopicBaseTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("
            CREATE TABLE app_topic_base (
                topic_id int8 NOT NULL GENERATED ALWAYS AS IDENTITY (INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 START 1 CACHE 1),
                topic_name varchar(100) NOT NULL DEFAULT '',
                cover_url varchar(500) NOT NULL DEFAULT '',
                description varchar(500) NOT NULL DEFAULT '',
                detail_html text NULL,
                creator_id int8 NOT NULL DEFAULT 0,
                sort_num int4 NOT NULL DEFAULT 0,
                is_recommend int2 NOT NULL DEFAULT 0,
                is_official int2 NOT NULL DEFAULT 0,
                status int2 NOT NULL DEFAULT 1,
                created_at timestamp(0) NULL,
                updated_at timestamp(0) NULL,
                deleted_at timestamp(0) NULL,
                PRIMARY KEY (topic_id)
            )
        ");

        // 列注释
        DB::statement("COMMENT ON COLUMN app_topic_base.topic_id IS '话题ID'");
        DB::statement("COMMENT ON COLUMN app_topic_base.topic_name IS '话题名称'");
        DB::statement("COMMENT ON COLUMN app_topic_base.cover_url IS '封面图URL'");
        DB::statement("COMMENT ON COLUMN app_topic_base.description IS '话题简介'");
        DB::statement("COMMENT ON COLUMN app_topic_base.detail_html IS '话题详情（富文本HTML）'");
        DB::statement("COMMENT ON COLUMN app_topic_base.creator_id IS '创建者ID'");
        DB::statement("COMMENT ON COLUMN app_topic_base.sort_num IS '排序号（越大越靠前）'");
        DB::statement("COMMENT ON COLUMN app_topic_base.is_recommend IS '是否推荐 0否 1是'");
        DB::statement("COMMENT ON COLUMN app_topic_base.is_official IS '是否官方话题 0否 1是'");
        DB::statement("COMMENT ON COLUMN app_topic_base.status IS '状态 1正常 2禁用'");
        DB::statement("COMMENT ON COLUMN app_topic_base.created_at IS '创建时间'");
        DB::statement("COMMENT ON COLUMN app_topic_base.updated_at IS '更新时间'");
        DB::statement("COMMENT ON COLUMN app_topic_base.deleted_at IS '删除时间'");

        // 索引
        DB::statement('CREATE INDEX idx_app_topic_base_status ON app_topic_base (status)');
        DB::statement('CREATE INDEX idx_app_topic_base_sort_num ON app_topic_base (sort_num)');
        DB::statement('CREATE INDEX idx_app_topic_base_is_recommend ON app_topic_base (is_recommend)');
        DB::statement('CREATE INDEX idx_app_topic_base_created_at ON app_topic_base (created_at)');

        // 表注释
        DB::statement("COMMENT ON TABLE app_topic_base IS '话题基础表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS app_topic_base');
    }
}
