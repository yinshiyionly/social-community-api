<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAppCourseCommentTable extends Migration
{
    public function up()
    {
        DB::statement("
            CREATE TABLE app_course_comment (
                comment_id int8 NOT NULL GENERATED ALWAYS AS IDENTITY (INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 START 1 CACHE 1),
                course_id int8 NOT NULL,
                member_id int8 NOT NULL,
                order_id int8 NULL,
                rating int2 NOT NULL DEFAULT 5,
                content text NULL,
                images jsonb NOT NULL DEFAULT '[]',
                is_anonymous int2 NOT NULL DEFAULT 0,
                like_count int4 NOT NULL DEFAULT 0,
                is_top int2 NOT NULL DEFAULT 0,
                is_excellent int2 NOT NULL DEFAULT 0,
                status int2 NOT NULL DEFAULT 1,
                reply_content text NULL,
                reply_time timestamp(0) NULL,
                reply_by int8 NULL,
                client_ip varchar(50) NULL,
                create_time timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP,
                update_time timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP,
                del_flag int2 NOT NULL DEFAULT 0,
                PRIMARY KEY (comment_id)
            )
        ");

        DB::statement("COMMENT ON COLUMN app_course_comment.comment_id IS '评价ID'");
        DB::statement("COMMENT ON COLUMN app_course_comment.course_id IS '课程ID'");
        DB::statement("COMMENT ON COLUMN app_course_comment.member_id IS '用户ID'");
        DB::statement("COMMENT ON COLUMN app_course_comment.order_id IS '订单ID'");
        DB::statement("COMMENT ON COLUMN app_course_comment.rating IS '评分：1-5'");
        DB::statement("COMMENT ON COLUMN app_course_comment.content IS '评价内容'");
        DB::statement("COMMENT ON COLUMN app_course_comment.images IS '评价图片'");
        DB::statement("COMMENT ON COLUMN app_course_comment.is_anonymous IS '是否匿名：0=否 1=是'");
        DB::statement("COMMENT ON COLUMN app_course_comment.like_count IS '点赞数'");
        DB::statement("COMMENT ON COLUMN app_course_comment.is_top IS '是否置顶：0=否 1=是'");
        DB::statement("COMMENT ON COLUMN app_course_comment.is_excellent IS '是否精选：0=否 1=是'");
        DB::statement("COMMENT ON COLUMN app_course_comment.status IS '状态：0=待审核 1=已通过 2=已拒绝'");
        DB::statement("COMMENT ON COLUMN app_course_comment.reply_content IS '商家回复'");
        DB::statement("COMMENT ON COLUMN app_course_comment.reply_time IS '回复时间'");
        DB::statement("COMMENT ON COLUMN app_course_comment.reply_by IS '回复人'");
        DB::statement("COMMENT ON COLUMN app_course_comment.del_flag IS '删除标志：0=正常 1=删除'");

        DB::statement('CREATE INDEX idx_app_course_comment_course_id ON app_course_comment (course_id)');
        DB::statement('CREATE INDEX idx_app_course_comment_member_id ON app_course_comment (member_id)');
        DB::statement('CREATE INDEX idx_app_course_comment_list ON app_course_comment (course_id, status, is_top)');
        DB::statement("COMMENT ON TABLE app_course_comment IS '课程评价表'");
    }

    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS app_course_comment');
    }
}
