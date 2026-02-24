<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAppMessageUnreadCountTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("
            CREATE TABLE app_message_unread_count (
                member_id int8 NOT NULL,
                like_count int4 NOT NULL DEFAULT 0,
                collect_count int4 NOT NULL DEFAULT 0,
                comment_count int4 NOT NULL DEFAULT 0,
                follow_count int4 NOT NULL DEFAULT 0,
                system_count int4 NOT NULL DEFAULT 0,
                created_at timestamp(0) NULL,
                updated_at timestamp(0) NULL,
                PRIMARY KEY (member_id)
            )
        ");

        // 列注释
        DB::statement("COMMENT ON COLUMN app_message_unread_count.member_id IS '会员ID'");
        DB::statement("COMMENT ON COLUMN app_message_unread_count.like_count IS '点赞未读数'");
        DB::statement("COMMENT ON COLUMN app_message_unread_count.collect_count IS '收藏未读数'");
        DB::statement("COMMENT ON COLUMN app_message_unread_count.comment_count IS '评论未读数'");
        DB::statement("COMMENT ON COLUMN app_message_unread_count.follow_count IS '关注未读数'");
        DB::statement("COMMENT ON COLUMN app_message_unread_count.system_count IS '系统消息总未读数'");

        // 表注释
        DB::statement("COMMENT ON TABLE app_message_unread_count IS '消息未读数统计表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS app_message_unread_count');
    }
}
