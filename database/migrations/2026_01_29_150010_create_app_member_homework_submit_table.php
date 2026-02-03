<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAppMemberHomeworkSubmitTable extends Migration
{
    public function up()
    {
        DB::statement("
            CREATE TABLE app_member_homework_submit (
                submit_id int8 NOT NULL GENERATED ALWAYS AS IDENTITY (INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 START 1 CACHE 1),
                homework_id int8 NOT NULL,
                chapter_id int8 NOT NULL,
                course_id int8 NOT NULL,
                member_id int8 NOT NULL,
                submit_content text NULL,
                submit_images jsonb NOT NULL DEFAULT '[]',
                submit_videos jsonb NOT NULL DEFAULT '[]',
                submit_files jsonb NOT NULL DEFAULT '[]',
                review_status int2 NOT NULL DEFAULT 0,
                review_content text NULL,
                reviewer_id int8 NULL,
                review_time timestamp(0) NULL,
                point_earned int4 NOT NULL DEFAULT 0,
                like_count int4 NOT NULL DEFAULT 0,
                comment_count int4 NOT NULL DEFAULT 0,
                is_excellent int2 NOT NULL DEFAULT 0,
                client_ip varchar(50) NULL,
                create_time timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP,
                update_time timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (submit_id)
            )
        ");

        DB::statement("COMMENT ON COLUMN app_member_homework_submit.submit_id IS '提交ID'");
        DB::statement("COMMENT ON COLUMN app_member_homework_submit.homework_id IS '作业ID'");
        DB::statement("COMMENT ON COLUMN app_member_homework_submit.chapter_id IS '章节ID'");
        DB::statement("COMMENT ON COLUMN app_member_homework_submit.course_id IS '课程ID'");
        DB::statement("COMMENT ON COLUMN app_member_homework_submit.member_id IS '用户ID'");
        DB::statement("COMMENT ON COLUMN app_member_homework_submit.submit_content IS '提交内容'");
        DB::statement("COMMENT ON COLUMN app_member_homework_submit.submit_images IS '提交图片'");
        DB::statement("COMMENT ON COLUMN app_member_homework_submit.submit_videos IS '提交视频'");
        DB::statement("COMMENT ON COLUMN app_member_homework_submit.submit_files IS '提交文件'");
        DB::statement("COMMENT ON COLUMN app_member_homework_submit.review_status IS '批改状态：0=待批改 1=已通过 2=需修改'");
        DB::statement("COMMENT ON COLUMN app_member_homework_submit.review_content IS '批改内容'");
        DB::statement("COMMENT ON COLUMN app_member_homework_submit.reviewer_id IS '批改人ID'");
        DB::statement("COMMENT ON COLUMN app_member_homework_submit.review_time IS '批改时间'");
        DB::statement("COMMENT ON COLUMN app_member_homework_submit.point_earned IS '获得积分'");
        DB::statement("COMMENT ON COLUMN app_member_homework_submit.like_count IS '点赞数'");
        DB::statement("COMMENT ON COLUMN app_member_homework_submit.comment_count IS '评论数'");
        DB::statement("COMMENT ON COLUMN app_member_homework_submit.is_excellent IS '是否优秀作业：0=否 1=是'");

        DB::statement('CREATE UNIQUE INDEX uk_app_member_homework_submit_homework_member ON app_member_homework_submit (homework_id, member_id)');
        DB::statement('CREATE INDEX idx_app_member_homework_submit_member_id ON app_member_homework_submit (member_id)');
        DB::statement('CREATE INDEX idx_app_member_homework_submit_course_id ON app_member_homework_submit (course_id)');
        DB::statement('CREATE INDEX idx_app_member_homework_submit_review_status ON app_member_homework_submit (review_status)');
        DB::statement("COMMENT ON TABLE app_member_homework_submit IS '用户作业提交表'");
    }

    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS app_member_homework_submit');
    }
}
