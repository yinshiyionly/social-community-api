<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAppMessageSystemUnreadTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("
            CREATE TABLE app_message_system_unread (
                id int8 NOT NULL GENERATED ALWAYS AS IDENTITY (INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 START 1 CACHE 1),
                member_id int8 NOT NULL,
                sender_id int8 NOT NULL,
                unread_count int4 NOT NULL DEFAULT 0,
                created_at timestamp(0) NULL,
                updated_at timestamp(0) NULL,
                PRIMARY KEY (id)
            )
        ");

        // 列注释
        DB::statement("COMMENT ON COLUMN app_message_system_unread.member_id IS '接收者会员ID'");
        DB::statement("COMMENT ON COLUMN app_message_system_unread.sender_id IS '发送者会员ID（官方账号）'");
        DB::statement("COMMENT ON COLUMN app_message_system_unread.unread_count IS '未读消息数'");

        // 唯一索引：每个会员每个发送者只有一条记录
        DB::statement('CREATE UNIQUE INDEX uk_app_message_system_unread_member_sender ON app_message_system_unread (member_id, sender_id)');
        DB::statement('CREATE INDEX idx_app_message_system_unread_member ON app_message_system_unread (member_id)');

        // 表注释
        DB::statement("COMMENT ON TABLE app_message_system_unread IS '系统消息按发送者未读数表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS app_message_system_unread');
    }
}
