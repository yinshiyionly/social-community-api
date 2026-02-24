<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAppMessageInteractionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("
            CREATE TABLE app_message_interaction (
                message_id int8 NOT NULL GENERATED ALWAYS AS IDENTITY (INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 START 1 CACHE 1),
                receiver_id int8 NOT NULL,
                sender_id int8 NOT NULL,
                message_type int2 NOT NULL,
                target_id int8 NULL,
                target_type int2 NULL,
                content_summary varchar(100) NULL,
                cover_url varchar(500) NULL,
                is_read int2 NOT NULL DEFAULT 0,
                created_at timestamp(0) NULL,
                updated_at timestamp(0) NULL,
                PRIMARY KEY (message_id)
            )
        ");

        // 列注释
        DB::statement("COMMENT ON COLUMN app_message_interaction.message_id IS '消息ID'");
        DB::statement("COMMENT ON COLUMN app_message_interaction.receiver_id IS '接收者ID'");
        DB::statement("COMMENT ON COLUMN app_message_interaction.sender_id IS '发送者ID'");
        DB::statement("COMMENT ON COLUMN app_message_interaction.message_type IS '消息类型：1=点赞 2=收藏 3=评论 4=关注'");
        DB::statement("COMMENT ON COLUMN app_message_interaction.target_id IS '目标ID（帖子ID/评论ID）'");
        DB::statement("COMMENT ON COLUMN app_message_interaction.target_type IS '目标类型：1=帖子 2=评论'");
        DB::statement("COMMENT ON COLUMN app_message_interaction.content_summary IS '内容摘要'");
        DB::statement("COMMENT ON COLUMN app_message_interaction.cover_url IS '封面图URL'");
        DB::statement("COMMENT ON COLUMN app_message_interaction.is_read IS '是否已读：0=未读 1=已读'");

        // 索引
        DB::statement('CREATE INDEX idx_app_message_interaction_receiver_type ON app_message_interaction (receiver_id, message_type, created_at DESC)');
        DB::statement('CREATE INDEX idx_app_message_interaction_receiver_read ON app_message_interaction (receiver_id, is_read)');
        DB::statement('CREATE INDEX idx_app_message_interaction_sender ON app_message_interaction (sender_id)');

        // 表注释
        DB::statement("COMMENT ON TABLE app_message_interaction IS '互动消息表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS app_message_interaction');
    }
}
