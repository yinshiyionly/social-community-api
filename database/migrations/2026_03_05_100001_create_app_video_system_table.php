<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAppVideoSystemTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("
            CREATE TABLE app_video_system (
                video_id int8 NOT NULL GENERATED ALWAYS AS IDENTITY (INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 START 1 CACHE 1),
                name varchar(255) NOT NULL DEFAULT '',
                status int2 NOT NULL DEFAULT 1,
                total_size varchar(50) NOT NULL DEFAULT '0',
                preface_url varchar(1024) NULL,
                play_url varchar(512) NULL,
                length int4 NOT NULL DEFAULT 0,
                width int4 NOT NULL DEFAULT 0,
                height int4 NOT NULL DEFAULT 0,
                created_at timestamp(0) NULL,
                updated_at timestamp(0) NULL,
                deleted_at timestamp(0) NULL,
                PRIMARY KEY (video_id)
            )
        ");

        DB::statement("COMMENT ON COLUMN app_video_system.video_id IS '视频ID'");
        DB::statement("COMMENT ON COLUMN app_video_system.name IS '视频名称'");
        DB::statement("COMMENT ON COLUMN app_video_system.status IS '状态：1=启用 2=禁用'");
        DB::statement("COMMENT ON COLUMN app_video_system.total_size IS '视频大小（字节）'");
        DB::statement("COMMENT ON COLUMN app_video_system.preface_url IS '封面图片地址'");
        DB::statement("COMMENT ON COLUMN app_video_system.play_url IS '视频播放地址'");
        DB::statement("COMMENT ON COLUMN app_video_system.length IS '视频时长（秒）'");
        DB::statement("COMMENT ON COLUMN app_video_system.width IS '视频宽度'");
        DB::statement("COMMENT ON COLUMN app_video_system.height IS '视频高度'");

        // 索引
        DB::statement('CREATE INDEX idx_app_video_system_status ON app_video_system (status)');

        DB::statement("COMMENT ON TABLE app_video_system IS '系统视频表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS app_video_system');
    }
}
