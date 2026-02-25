<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAppAdItemTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("
            CREATE TABLE app_ad_item (
                ad_id int8 NOT NULL GENERATED ALWAYS AS IDENTITY (INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 START 1 CACHE 1),
                space_id int8 NOT NULL,
                ad_title varchar(100) NOT NULL DEFAULT '',
                ad_type varchar(50) NOT NULL DEFAULT '',
                content_url text NOT NULL DEFAULT '',
                target_type varchar(50) NOT NULL DEFAULT '',
                target_url varchar(500) NOT NULL DEFAULT '',
                sort_num int4 NOT NULL DEFAULT 0,
                status int2 NOT NULL DEFAULT 1,
                start_time timestamp(6) NULL,
                end_time timestamp(6) NULL,
                ext_json jsonb NOT NULL DEFAULT '{}'::jsonb,
                created_at timestamp(6) NOT NULL DEFAULT now(),
                updated_at timestamp(6) NOT NULL DEFAULT now(),
                deleted_at timestamp(6) NULL,
                PRIMARY KEY (ad_id)
            )
        ");

        // 列注释
        DB::statement("COMMENT ON COLUMN app_ad_item.ad_id IS '广告ID'");
        DB::statement("COMMENT ON COLUMN app_ad_item.space_id IS '广告位ID'");
        DB::statement("COMMENT ON COLUMN app_ad_item.ad_title IS '广告标题/内部备注'");
        DB::statement("COMMENT ON COLUMN app_ad_item.ad_type IS '广告素材类型: image-图片, video-视频, text-文字, html'");
        DB::statement("COMMENT ON COLUMN app_ad_item.content_url IS '图片、视频、动图的素材地址'");
        DB::statement("COMMENT ON COLUMN app_ad_item.target_type IS '跳转类型: external-外链, internal-内部路由, none-不跳转'");
        DB::statement("COMMENT ON COLUMN app_ad_item.target_url IS '跳转目标地址或页面路径'");
        DB::statement("COMMENT ON COLUMN app_ad_item.sort_num IS '展示优先级，数值越大越靠前'");
        DB::statement("COMMENT ON COLUMN app_ad_item.status IS '状态: 1-上线, 2-下线'");
        DB::statement("COMMENT ON COLUMN app_ad_item.start_time IS '生效时间'");
        DB::statement("COMMENT ON COLUMN app_ad_item.end_time IS '失效时间'");
        DB::statement("COMMENT ON COLUMN app_ad_item.ext_json IS '扩展配置，如按钮文字、背景色、角标等'");
        DB::statement("COMMENT ON COLUMN app_ad_item.created_at IS '创建时间'");
        DB::statement("COMMENT ON COLUMN app_ad_item.updated_at IS '更新时间'");
        DB::statement("COMMENT ON COLUMN app_ad_item.deleted_at IS '删除时间'");

        // 索引
        DB::statement('CREATE INDEX idx_ad_space ON app_ad_item (space_id)');

        // 表注释
        DB::statement("COMMENT ON TABLE app_ad_item IS '广告内容表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS app_ad_item');
    }
}
