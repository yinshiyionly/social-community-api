<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAppPostBaseTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 使用原生 SQL 创建表，支持 GENERATED ALWAYS AS IDENTITY
        DB::statement("
            CREATE TABLE app_post_base (
                post_id int8 NOT NULL GENERATED ALWAYS AS IDENTITY (INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 START 350785364789 CACHE 1),
                post_type int2 NOT NULL DEFAULT 1,
                member_id int8 NOT NULL,
                title text NOT NULL DEFAULT '',
                content text NOT NULL DEFAULT '',
                media_data jsonb NOT NULL DEFAULT '[]',
                cover jsonb NOT NULL DEFAULT '{}',
                image_show_style int2 NOT NULL DEFAULT 1,
                article_cover_style int2 NOT NULL DEFAULT 1,
                is_top int2 NOT NULL DEFAULT 0,
                sort_score numeric(16,6) NOT NULL DEFAULT 0,
                visible int2 NOT NULL DEFAULT 1,
                status int2 NOT NULL DEFAULT 0,
                created_at timestamp(0) NULL,
                updated_at timestamp(0) NULL,
                deleted_at timestamp(0) NULL,
                PRIMARY KEY (post_id)
            )
        ");

        // 添加列注释
        DB::statement("COMMENT ON COLUMN app_post_base.post_type IS '动态类型：1=图文 2=视频 3=文章'");
        DB::statement("COMMENT ON COLUMN app_post_base.member_id IS '会员ID'");
        DB::statement("COMMENT ON COLUMN app_post_base.title IS '标题'");
        DB::statement("COMMENT ON COLUMN app_post_base.content IS '内容'");
        DB::statement("COMMENT ON COLUMN app_post_base.media_data IS '媒体数据'");
        DB::statement("COMMENT ON COLUMN app_post_base.cover IS '封面图信息：{url, width, height}'");
        DB::statement("COMMENT ON COLUMN app_post_base.image_show_style IS '图文动态图片展示样式：1=大图 2=拼图'");
        DB::statement("COMMENT ON COLUMN app_post_base.article_cover_style IS '文章封面样式：1=单图 2=双图 3=三图'");
        DB::statement("COMMENT ON COLUMN app_post_base.is_top IS '是否置顶：0=否 1=是'");
        DB::statement("COMMENT ON COLUMN app_post_base.sort_score IS '排序分（物化计算）'");
        DB::statement("COMMENT ON COLUMN app_post_base.visible IS '可见性：0=私密 1=公开'");
        DB::statement("COMMENT ON COLUMN app_post_base.status IS '状态：0=待审核 1=已通过 2=已拒绝'");

        // 索引
        DB::statement('CREATE INDEX idx_app_post_base_post_type ON app_post_base (post_type)');
        DB::statement('CREATE INDEX idx_app_post_base_member_id ON app_post_base (member_id)');
        DB::statement('CREATE INDEX idx_app_post_base_status ON app_post_base (status)');
        DB::statement('CREATE INDEX idx_app_post_base_created_at ON app_post_base (created_at)');

        // 复合索引用于列表排序
        DB::statement('CREATE INDEX idx_app_post_base_list_sort ON app_post_base (status, visible, is_top DESC, sort_score DESC, post_id DESC)');

        DB::statement("COMMENT ON TABLE app_post_base IS '动态基础表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS app_post_base');
    }
}
