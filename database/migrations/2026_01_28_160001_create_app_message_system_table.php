<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAppMessageSystemTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("
            CREATE TABLE app_message_system (
                message_id int8 NOT NULL GENERATED ALWAYS AS IDENTITY (INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 START 1 CACHE 1),
                sender_id int8 NULL,
                receiver_id int8 NULL,
                title varchar(100) NOT NULL DEFAULT '',
                content text NOT NULL DEFAULT '',
                cover_url varchar(500) NULL,
                link_type int2 NULL,
                link_url varchar(500) NULL,
                is_read int2 NOT NULL DEFAULT 0,
                created_at timestamp(0) NULL,
                updated_at timestamp(0) NULL,
                PRIMARY KEY (message_id)
            )
        ");

        // 列注释
        DB::statement("COMMENT ON COLUMN app_message_system.message_id IS '消息ID'");
        DB::statement("COMMENT ON COLUMN app_message_system.sender_id IS '发送者会员ID（官方账号的member_id）'");
        DB::statement("COMMENT ON COLUMN app_message_system.receiver_id IS '接收者会员ID（NULL表示全员广播）'");
        DB::statement("COMMENT ON COLUMN app_message_system.title IS '消息标题'");
        DB::statement("COMMENT ON COLUMN app_message_system.content IS '消息内容'");
        DB::statement("COMMENT ON COLUMN app_message_system.cover_url IS '封面图URL'");
        DB::statement("COMMENT ON COLUMN app_message_system.link_type IS '跳转类型：1=帖子详情 2=活动页 3=外链 4=无跳转'");
        DB::statement("COMMENT ON COLUMN app_message_system.link_url IS '跳转链接/目标ID'");
        DB::statement("COMMENT ON COLUMN app_message_system.is_read IS '是否已读：0=未读 1=已读'");

        // 索引
        DB::statement('CREATE INDEX idx_app_message_system_receiver ON app_message_system (receiver_id, created_at DESC)');
        DB::statement('CREATE INDEX idx_app_message_system_sender_receiver ON app_message_system (sender_id, receiver_id, created_at DESC)');

        // 表注释
        DB::statement("COMMENT ON TABLE app_message_system IS '系统消息表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS app_message_system');
    }
}
