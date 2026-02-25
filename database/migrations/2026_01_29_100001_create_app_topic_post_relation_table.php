<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAppTopicPostRelationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("
            CREATE TABLE app_topic_post_relation (
                id int8 NOT NULL GENERATED ALWAYS AS IDENTITY (INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 START 1 CACHE 1),
                topic_id int8 NOT NULL,
                post_id int8 NOT NULL,
                member_id int8 NOT NULL,
                is_featured int2 NOT NULL DEFAULT 0,
                created_at timestamp(0) NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            )
        ");

        // 列注释
        DB::statement("COMMENT ON COLUMN app_topic_post_relation.id IS 'ID'");
        DB::statement("COMMENT ON COLUMN app_topic_post_relation.topic_id IS '话题ID'");
        DB::statement("COMMENT ON COLUMN app_topic_post_relation.post_id IS '帖子ID'");
        DB::statement("COMMENT ON COLUMN app_topic_post_relation.member_id IS '发帖人ID'");
        DB::statement("COMMENT ON COLUMN app_topic_post_relation.is_featured IS '是否精选 0否 1是'");
        DB::statement("COMMENT ON COLUMN app_topic_post_relation.created_at IS '创建时间'");

        // 索引
        DB::statement('CREATE UNIQUE INDEX uk_app_topic_post_relation_topic_post ON app_topic_post_relation (topic_id, post_id)');
        DB::statement('CREATE INDEX idx_app_topic_post_relation_topic_created ON app_topic_post_relation (topic_id, created_at)');
        DB::statement('CREATE INDEX idx_app_topic_post_relation_post_id ON app_topic_post_relation (post_id)');
        DB::statement('CREATE INDEX idx_app_topic_post_relation_topic_member ON app_topic_post_relation (topic_id, member_id)');

        // 表注释
        DB::statement("COMMENT ON TABLE app_topic_post_relation IS '帖子话题关联表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS app_topic_post_relation');
    }
}
