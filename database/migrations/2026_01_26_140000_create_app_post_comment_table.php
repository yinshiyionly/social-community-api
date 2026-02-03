<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAppPostCommentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("
            CREATE TABLE app_post_comment (
                comment_id int8 NOT NULL GENERATED ALWAYS AS IDENTITY (INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 START 1 CACHE 1),
                post_id int8 NOT NULL,
                member_id int8 NOT NULL,
                parent_id int8 NOT NULL DEFAULT 0,
                reply_to_member_id int8 NOT NULL DEFAULT 0,
                content text NOT NULL DEFAULT '',
                like_count int4 NOT NULL DEFAULT 0,
                reply_count int4 NOT NULL DEFAULT 0,
                status int2 NOT NULL DEFAULT 1,
                ip_address inet NULL,
                ip_region varchar(100) NOT NULL DEFAULT '',
                created_at timestamp(0) NULL,
                updated_at timestamp(0) NULL,
                deleted_at timestamp(0) NULL,
                PRIMARY KEY (comment_id)
            )
        ");

        // 列注释
        DB::statement("COMMENT ON COLUMN app_post_comment.comment_id IS '评论ID'");
        DB::statement("COMMENT ON COLUMN app_post_comment.post_id IS '帖子ID'");
        DB::statement("COMMENT ON COLUMN app_post_comment.member_id IS '评论者ID'");
        DB::statement("COMMENT ON COLUMN app_post_comment.parent_id IS '父评论ID，0表示一级评论'");
        DB::statement("COMMENT ON COLUMN app_post_comment.reply_to_member_id IS '回复目标用户ID'");
        DB::statement("COMMENT ON COLUMN app_post_comment.content IS '评论内容'");
        DB::statement("COMMENT ON COLUMN app_post_comment.like_count IS '点赞数'");
        DB::statement("COMMENT ON COLUMN app_post_comment.reply_count IS '回复数'");
        DB::statement("COMMENT ON COLUMN app_post_comment.status IS '状态：0=待审核 1=正常 2=已删除'");
        DB::statement("COMMENT ON COLUMN app_post_comment.ip_address IS 'IP地址'");
        DB::statement("COMMENT ON COLUMN app_post_comment.ip_region IS 'IP归属地'");

        // 索引
        DB::statement('CREATE INDEX idx_app_post_comment_post_id ON app_post_comment (post_id)');
        DB::statement('CREATE INDEX idx_app_post_comment_member_id ON app_post_comment (member_id)');
        DB::statement('CREATE INDEX idx_app_post_comment_parent_id ON app_post_comment (parent_id)');
        DB::statement('CREATE INDEX idx_app_post_comment_list ON app_post_comment (post_id, status, created_at)');

        // 表注释
        DB::statement("COMMENT ON TABLE app_post_comment IS '帖子评论表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS app_post_comment');
    }
}
