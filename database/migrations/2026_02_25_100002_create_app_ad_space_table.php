<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAppAdSpaceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("
            CREATE TABLE app_ad_space (
                space_id int8 NOT NULL GENERATED ALWAYS AS IDENTITY (INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 START 1 CACHE 1),
                space_name varchar(50) NOT NULL DEFAULT '',
                space_code varchar(50) NOT NULL DEFAULT '',
                platform int2 NOT NULL DEFAULT 0,
                width int4 NOT NULL DEFAULT 0,
                height int4 NOT NULL DEFAULT 0,
                max_ads int4 NOT NULL DEFAULT 0,
                status int2 NOT NULL DEFAULT 1,
                created_at timestamp(6) NOT NULL DEFAULT now(),
                updated_at timestamp(6) NOT NULL DEFAULT now(),
                deleted_at timestamp(6) NULL,
                PRIMARY KEY (space_id)
            )
        ");

        // 列注释
        DB::statement("COMMENT ON COLUMN app_ad_space.space_id IS '广告位ID'");
        DB::statement("COMMENT ON COLUMN app_ad_space.space_name IS '广告位名称'");
        DB::statement("COMMENT ON COLUMN app_ad_space.space_code IS '广告位code，唯一，程序使用'");
        DB::statement("COMMENT ON COLUMN app_ad_space.platform IS '平台: 0-全端, 1-iOS, 2-安卓'");
        DB::statement("COMMENT ON COLUMN app_ad_space.width IS '广告位宽度'");
        DB::statement("COMMENT ON COLUMN app_ad_space.height IS '广告位高度'");
        DB::statement("COMMENT ON COLUMN app_ad_space.max_ads IS '该位置同时展示的最大广告数目'");
        DB::statement("COMMENT ON COLUMN app_ad_space.status IS '状态: 1-启用, 2-禁用'");
        DB::statement("COMMENT ON COLUMN app_ad_space.created_at IS '创建时间'");
        DB::statement("COMMENT ON COLUMN app_ad_space.updated_at IS '更新时间'");
        DB::statement("COMMENT ON COLUMN app_ad_space.deleted_at IS '删除时间'");

        // 表注释
        DB::statement("COMMENT ON TABLE app_ad_space IS '广告位表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS app_ad_space');
    }
}
