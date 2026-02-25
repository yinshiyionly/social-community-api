<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAppMemberPointTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("
            CREATE TABLE app_member_point (
                id int8 NOT NULL GENERATED ALWAYS AS IDENTITY (INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 START 1 CACHE 1),
                member_id int8 NOT NULL,
                total_points int8 NOT NULL DEFAULT 0,
                used_points int8 NOT NULL DEFAULT 0,
                available_points int8 NOT NULL DEFAULT 0,
                frozen_points int8 NOT NULL DEFAULT 0,
                expired_points int8 NOT NULL DEFAULT 0,
                level_points int8 NOT NULL DEFAULT 0,
                created_at timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP,
                deleted_at timestamp(0) NULL,
                PRIMARY KEY (id)
            )
        ");

        // 列注释
        DB::statement("COMMENT ON COLUMN app_member_point.id IS 'ID'");
        DB::statement("COMMENT ON COLUMN app_member_point.member_id IS '用户ID'");
        DB::statement("COMMENT ON COLUMN app_member_point.total_points IS '累计获得积分'");
        DB::statement("COMMENT ON COLUMN app_member_point.used_points IS '已使用积分'");
        DB::statement("COMMENT ON COLUMN app_member_point.available_points IS '可用积分'");
        DB::statement("COMMENT ON COLUMN app_member_point.frozen_points IS '冻结积分'");
        DB::statement("COMMENT ON COLUMN app_member_point.expired_points IS '已过期积分'");
        DB::statement("COMMENT ON COLUMN app_member_point.level_points IS '等级积分'");
        DB::statement("COMMENT ON COLUMN app_member_point.created_at IS '创建时间'");
        DB::statement("COMMENT ON COLUMN app_member_point.updated_at IS '更新时间'");
        DB::statement("COMMENT ON COLUMN app_member_point.deleted_at IS '删除时间'");

        // 索引
        DB::statement('CREATE UNIQUE INDEX uk_app_member_point_member_id ON app_member_point (member_id)');
        DB::statement('CREATE INDEX idx_app_member_point_available ON app_member_point (available_points)');

        // 表注释
        DB::statement("COMMENT ON TABLE app_member_point IS '用户积分账户表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS app_member_point');
    }
}
