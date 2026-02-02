<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAppPostCommentLikeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("
            CREATE TABLE app_post_comment_like (
                like_id int8 NOT NULL GENERATED ALWAYS AS IDENTITY (INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 START 1 CACHE 1),
                member_id int8 NOT NULL,
                comment_id int8 NOT NULL,
                created_at timestamp(0) NULL,
                updated_at timestamp(0) NULL,
                PRIMARY KEY (like_id)
            )
        ");

        // 列注释
        DB::statement("COMMENT ON COLUMN app_post_comment_like.like_id IS '点赞ID'");
        DB::statement("COMMENT ON COLUMN app_post_comment_like.member_id IS '会员ID'");
        DB::statement("COMMENT ON COLUMN app_post_comment_like.comment_id IS '评论ID'");

        // 唯一索引，防止重复点赞
        DB::statement('CREATE UNIQUE INDEX uk_app_post_comment_like_member_comment ON app_post_comment_like (member_id, comment_id)');
        // 查询索引
        DB::statement('CREATE INDEX idx_app_post_comment_like_member_id ON app_post_comment_like (member_id)');
        DB::statement('CREATE INDEX idx_app_post_comment_like_comment_id ON app_post_comment_like (comment_id)');

        // 表注释
        DB::statement("COMMENT ON TABLE app_post_comment_like IS '帖子评论点赞表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS app_post_comment_like');
    }
}
