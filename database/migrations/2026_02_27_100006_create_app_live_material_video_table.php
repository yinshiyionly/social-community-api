<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAppLiveMaterialVideoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("
            CREATE TABLE app_live_material_video (
                id int8 NOT NULL GENERATED ALWAYS AS IDENTITY (INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 START 1 CACHE 1),
                video_id int8 NOT NULL,
                name varchar(255) NOT NULL DEFAULT '',
                status int2 NOT NULL DEFAULT 10,
                total_size varchar(50) NOT NULL DEFAULT '0',
                preface_url varchar(1024) NULL,
                play_url varchar(512) NULL,
                length int4 NOT NULL DEFAULT 0,
                width int4 NOT NULL DEFAULT 0,
                height int4 NOT NULL DEFAULT 0,
                publish_status int2 NOT NULL DEFAULT 0,
                storage varchar(10) NOT NULL DEFAULT 'bjy',
                created_at timestamp(0) NULL,
                updated_at timestamp(0) NULL,
                deleted_at timestamp(0) NULL,
                PRIMARY KEY (id)
            )
        ");

        DB::statement("COMMENT ON COLUMN app_live_material_video.id IS '主键'");
        DB::statement("COMMENT ON COLUMN app_live_material_video.video_id IS '视频ID'");
        DB::statement("COMMENT ON COLUMN app_live_material_video.name IS '视频名称'");
        DB::statement("COMMENT ON COLUMN app_live_material_video.status IS '转码状态：10=上传中 20=转码中 30=转码失败 31=转码超时 32=上传超时 100=转码成功'");
        DB::statement("COMMENT ON COLUMN app_live_material_video.total_size IS '视频大小（字节）'");
        DB::statement("COMMENT ON COLUMN app_live_material_video.preface_url IS '封面图片地址'");
        DB::statement("COMMENT ON COLUMN app_live_material_video.play_url IS '视频播放地址'");
        DB::statement("COMMENT ON COLUMN app_live_material_video.length IS '视频时长（秒）'");
        DB::statement("COMMENT ON COLUMN app_live_material_video.width IS '视频宽度'");
        DB::statement("COMMENT ON COLUMN app_live_material_video.height IS '视频高度'");
        DB::statement("COMMENT ON COLUMN app_live_material_video.publish_status IS '发布状态：0=未发布 1=已发布'");
        DB::statement("COMMENT ON COLUMN app_live_material_video.storage IS '存储位置：bjy=百家云 tos=火山云'");

        // 索引
        DB::statement('CREATE INDEX idx_app_live_material_video_video_id ON app_live_material_video (video_id)');
        DB::statement('CREATE INDEX idx_app_live_material_video_status ON app_live_material_video (status)');
        DB::statement('CREATE INDEX idx_app_live_material_video_publish_status ON app_live_material_video (publish_status)');

        DB::statement("COMMENT ON TABLE app_live_material_video IS '直播素材视频表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS app_live_material_video');
    }
}
