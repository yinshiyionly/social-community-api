<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAppLiveRoomTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("
            CREATE TABLE app_live_room (
                room_id int8 NOT NULL GENERATED ALWAYS AS IDENTITY (INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 START 1 CACHE 1),
                room_title varchar(200) NOT NULL DEFAULT '',
                room_cover varchar(500) NULL,
                room_intro text NOT NULL DEFAULT '',
                live_type int2 NOT NULL DEFAULT 1,
                live_platform varchar(50) NOT NULL DEFAULT 'custom',
                third_party_room_id varchar(100) NULL,
                push_url varchar(500) NULL,
                pull_url varchar(500) NULL,
                video_url varchar(500) NULL,
                anchor_id int8 NULL,
                anchor_name varchar(100) NOT NULL DEFAULT '',
                anchor_avatar varchar(500) NULL,
                scheduled_start_time timestamp(0) NULL,
                scheduled_end_time timestamp(0) NULL,
                actual_start_time timestamp(0) NULL,
                actual_end_time timestamp(0) NULL,
                live_duration int4 NOT NULL DEFAULT 0,
                live_status int2 NOT NULL DEFAULT 0,
                allow_chat int2 NOT NULL DEFAULT 1,
                allow_gift int2 NOT NULL DEFAULT 0,
                allow_like int2 NOT NULL DEFAULT 1,
                password varchar(100) NULL,
                ext_config jsonb NOT NULL DEFAULT '{}',
                status int2 NOT NULL DEFAULT 1,
                created_by int8 NULL,
                updated_by int8 NULL,
                deleted_by int8 NULL,
                created_at timestamp(0) NULL,
                updated_at timestamp(0) NULL,
                deleted_at timestamp(0) NULL,
                PRIMARY KEY (room_id)
            )
        ");

        // 列注释
        DB::statement("COMMENT ON COLUMN app_live_room.room_id IS '直播间ID'");
        DB::statement("COMMENT ON COLUMN app_live_room.room_title IS '直播间标题'");
        DB::statement("COMMENT ON COLUMN app_live_room.room_cover IS '直播间封面'");
        DB::statement("COMMENT ON COLUMN app_live_room.room_intro IS '直播间简介'");
        DB::statement("COMMENT ON COLUMN app_live_room.live_type IS '直播类型：1=真实直播 2=伪直播'");
        DB::statement("COMMENT ON COLUMN app_live_room.live_platform IS '直播平台：custom/baijiayun/aliyun/tencent/agora'");
        DB::statement("COMMENT ON COLUMN app_live_room.third_party_room_id IS '第三方平台直播间ID'");
        DB::statement("COMMENT ON COLUMN app_live_room.push_url IS '推流地址'");
        DB::statement("COMMENT ON COLUMN app_live_room.pull_url IS '拉流地址'");
        DB::statement("COMMENT ON COLUMN app_live_room.video_url IS '伪直播视频地址'");
        DB::statement("COMMENT ON COLUMN app_live_room.anchor_id IS '主播用户ID'");
        DB::statement("COMMENT ON COLUMN app_live_room.anchor_name IS '主播名称'");
        DB::statement("COMMENT ON COLUMN app_live_room.anchor_avatar IS '主播头像'");
        DB::statement("COMMENT ON COLUMN app_live_room.scheduled_start_time IS '计划开始时间'");
        DB::statement("COMMENT ON COLUMN app_live_room.scheduled_end_time IS '计划结束时间'");
        DB::statement("COMMENT ON COLUMN app_live_room.actual_start_time IS '实际开始时间'");
        DB::statement("COMMENT ON COLUMN app_live_room.actual_end_time IS '实际结束时间'");
        DB::statement("COMMENT ON COLUMN app_live_room.live_duration IS '预计时长（分钟）'");
        DB::statement("COMMENT ON COLUMN app_live_room.live_status IS '直播状态：0=未开始 1=直播中 2=已结束 3=已取消'");
        DB::statement("COMMENT ON COLUMN app_live_room.allow_chat IS '允许聊天：0=否 1=是'");
        DB::statement("COMMENT ON COLUMN app_live_room.allow_gift IS '允许送礼：0=否 1=是'");
        DB::statement("COMMENT ON COLUMN app_live_room.allow_like IS '允许点赞：0=否 1=是'");
        DB::statement("COMMENT ON COLUMN app_live_room.password IS '直播间密码（加密存储）'");
        DB::statement("COMMENT ON COLUMN app_live_room.ext_config IS '扩展配置（JSON）'");
        DB::statement("COMMENT ON COLUMN app_live_room.status IS '状态：0=禁用 1=启用'");
        DB::statement("COMMENT ON COLUMN app_live_room.created_by IS '创建人ID'");
        DB::statement("COMMENT ON COLUMN app_live_room.updated_by IS '更新人ID'");
        DB::statement("COMMENT ON COLUMN app_live_room.deleted_by IS '删除人ID'");

        // 索引
        DB::statement('CREATE INDEX idx_app_live_room_live_type ON app_live_room (live_type)');
        DB::statement('CREATE INDEX idx_app_live_room_live_platform ON app_live_room (live_platform)');
        DB::statement('CREATE INDEX idx_app_live_room_live_status ON app_live_room (live_status)');
        DB::statement('CREATE INDEX idx_app_live_room_status ON app_live_room (status)');
        DB::statement('CREATE INDEX idx_app_live_room_scheduled_start_time ON app_live_room (scheduled_start_time)');
        DB::statement('CREATE INDEX idx_app_live_room_anchor_id ON app_live_room (anchor_id)');
        DB::statement('CREATE INDEX idx_app_live_room_third_party_room_id ON app_live_room (third_party_room_id)');

        // 表注释
        DB::statement("COMMENT ON TABLE app_live_room IS '直播间基础表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS app_live_room');
    }
}
