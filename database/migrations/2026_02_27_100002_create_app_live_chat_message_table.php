<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAppLiveChatMessageTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("
            CREATE TABLE app_live_chat_message (
                message_id int8 NOT NULL GENERATED ALWAYS AS IDENTITY (INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 START 1 CACHE 1),
                room_id int8 NOT NULL,
                member_id int8 NOT NULL,
                member_name varchar(100) NOT NULL DEFAULT '',
                member_avatar varchar(500) NULL,
                message_type int2 NOT NULL DEFAULT 1,
                content text NOT NULL DEFAULT '',
                ext_data jsonb NOT NULL DEFAULT '{}',
                is_top int2 NOT NULL DEFAULT 0,
                is_blocked int2 NOT NULL DEFAULT 0,
                created_at timestamp(0) NULL,
                PRIMARY KEY (message_id)
            )
        ");

        DB::statement("COMMENT ON COLUMN app_live_chat_message.message_id IS '消息ID'");
        DB::statement("COMMENT ON COLUMN app_live_chat_message.room_id IS '直播间ID'");
        DB::statement("COMMENT ON COLUMN app_live_chat_message.member_id IS '发送者ID'");
        DB::statement("COMMENT ON COLUMN app_live_chat_message.member_name IS '发送者昵称'");
        DB::statement("COMMENT ON COLUMN app_live_chat_message.member_avatar IS '发送者头像'");
        DB::statement("COMMENT ON COLUMN app_live_chat_message.message_type IS '消息类型：1=文本 2=图片 3=礼物 4=系统消息 5=红包'");
        DB::statement("COMMENT ON COLUMN app_live_chat_message.content IS '消息内容'");
        DB::statement("COMMENT ON COLUMN app_live_chat_message.ext_data IS '扩展数据（礼物信息/图片URL等）'");
        DB::statement("COMMENT ON COLUMN app_live_chat_message.is_top IS '是否置顶：0=否 1=是'");
        DB::statement("COMMENT ON COLUMN app_live_chat_message.is_blocked IS '是否屏蔽：0=否 1=是'");

        // 索引
        DB::statement('CREATE INDEX idx_app_live_chat_message_room_id ON app_live_chat_message (room_id, created_at)');
        DB::statement('CREATE INDEX idx_app_live_chat_message_member_id ON app_live_chat_message (member_id)');
        DB::statement('CREATE INDEX idx_app_live_chat_message_type ON app_live_chat_message (room_id, message_type)');

        DB::statement("COMMENT ON TABLE app_live_chat_message IS '直播间聊天消息表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS app_live_chat_message');
    }
}
