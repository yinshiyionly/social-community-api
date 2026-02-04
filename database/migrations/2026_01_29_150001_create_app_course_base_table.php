<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAppCourseBaseTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("
            CREATE TABLE app_course_base (
                course_id int8 NOT NULL GENERATED ALWAYS AS IDENTITY (INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 START 100000001 CACHE 1),
                course_no varchar(32) NOT NULL DEFAULT '',
                category_id int4 NOT NULL DEFAULT 0,
                course_title varchar(200) NOT NULL DEFAULT '',
                course_subtitle varchar(300) NULL,
                pay_type int2 NOT NULL DEFAULT 1,
                play_type int2 NOT NULL DEFAULT 1,
                schedule_type int2 NOT NULL DEFAULT 1,
                cover_image varchar(500) NULL,
                cover_video varchar(500) NULL,
                banner_images jsonb NOT NULL DEFAULT '[]',
                intro_video varchar(500) NULL,
                brief text NULL,
                description text NULL,
                suitable_crowd text NULL,
                learn_goal text NULL,
                teacher_id int8 NULL,
                assistant_ids jsonb NOT NULL DEFAULT '[]',
                original_price numeric(10,2) NOT NULL DEFAULT 0,
                current_price numeric(10,2) NOT NULL DEFAULT 0,
                point_price int4 NOT NULL DEFAULT 0,
                is_free int2 NOT NULL DEFAULT 0,
                total_chapter int4 NOT NULL DEFAULT 0,
                total_duration int4 NOT NULL DEFAULT 0,
                valid_days int4 NOT NULL DEFAULT 0,
                allow_download int2 NOT NULL DEFAULT 0,
                allow_comment int2 NOT NULL DEFAULT 1,
                allow_share int2 NOT NULL DEFAULT 1,
                enroll_count int4 NOT NULL DEFAULT 0,
                view_count int4 NOT NULL DEFAULT 0,
                complete_count int4 NOT NULL DEFAULT 0,
                comment_count int4 NOT NULL DEFAULT 0,
                avg_rating numeric(2,1) NOT NULL DEFAULT 5.0,
                sort_order int4 NOT NULL DEFAULT 0,
                is_recommend int2 NOT NULL DEFAULT 0,
                is_hot int2 NOT NULL DEFAULT 0,
                is_new int2 NOT NULL DEFAULT 0,
                status int2 NOT NULL DEFAULT 0,
                publish_time timestamp(0) NULL,
                created_at timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP,
                created_by varchar(64) NULL,
                updated_at timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP,
                updated_by varchar(64) NULL,
                deleted_at timestamp(0) NULL,
                deleted_by varchar(64) NULL,
                PRIMARY KEY (course_id)
            )
        ");

        // 列注释
        DB::statement("COMMENT ON COLUMN app_course_base.course_id IS '课程ID'");
        DB::statement("COMMENT ON COLUMN app_course_base.course_no IS '课程编号'");
        DB::statement("COMMENT ON COLUMN app_course_base.category_id IS '分类ID'");
        DB::statement("COMMENT ON COLUMN app_course_base.course_title IS '课程标题'");
        DB::statement("COMMENT ON COLUMN app_course_base.course_subtitle IS '课程副标题'");
        DB::statement("COMMENT ON COLUMN app_course_base.pay_type IS '付费类型：1=体验课 2=小白课 3=进阶课 4=付费课'");
        DB::statement("COMMENT ON COLUMN app_course_base.play_type IS '播放类型：1=图文课 2=录播课 3=直播课 4=音频课'");
        DB::statement("COMMENT ON COLUMN app_course_base.schedule_type IS '排课类型：1=固定日期 2=动态解锁'");
        DB::statement("COMMENT ON COLUMN app_course_base.cover_image IS '封面图'");
        DB::statement("COMMENT ON COLUMN app_course_base.cover_video IS '封面视频'");
        DB::statement("COMMENT ON COLUMN app_course_base.banner_images IS '轮播图列表'");
        DB::statement("COMMENT ON COLUMN app_course_base.intro_video IS '课程介绍视频'");
        DB::statement("COMMENT ON COLUMN app_course_base.brief IS '课程简介'");
        DB::statement("COMMENT ON COLUMN app_course_base.description IS '课程详情（富文本）'");
        DB::statement("COMMENT ON COLUMN app_course_base.suitable_crowd IS '适合人群'");
        DB::statement("COMMENT ON COLUMN app_course_base.learn_goal IS '学习目标'");
        DB::statement("COMMENT ON COLUMN app_course_base.teacher_id IS '主讲师ID'");
        DB::statement("COMMENT ON COLUMN app_course_base.assistant_ids IS '助教ID列表'");
        DB::statement("COMMENT ON COLUMN app_course_base.original_price IS '原价'");
        DB::statement("COMMENT ON COLUMN app_course_base.current_price IS '现价'");
        DB::statement("COMMENT ON COLUMN app_course_base.point_price IS '积分价格'");
        DB::statement("COMMENT ON COLUMN app_course_base.is_free IS '是否免费：0=否 1=是'");
        DB::statement("COMMENT ON COLUMN app_course_base.total_chapter IS '总章节数'");
        DB::statement("COMMENT ON COLUMN app_course_base.total_duration IS '总时长（秒）'");
        DB::statement("COMMENT ON COLUMN app_course_base.valid_days IS '有效期天数（0=永久）'");
        DB::statement("COMMENT ON COLUMN app_course_base.allow_download IS '允许下载：0=否 1=是'");
        DB::statement("COMMENT ON COLUMN app_course_base.allow_comment IS '允许评论：0=否 1=是'");
        DB::statement("COMMENT ON COLUMN app_course_base.allow_share IS '允许分享：0=否 1=是'");
        DB::statement("COMMENT ON COLUMN app_course_base.enroll_count IS '报名人数'");
        DB::statement("COMMENT ON COLUMN app_course_base.view_count IS '浏览次数'");
        DB::statement("COMMENT ON COLUMN app_course_base.complete_count IS '完课人数'");
        DB::statement("COMMENT ON COLUMN app_course_base.comment_count IS '评论数'");
        DB::statement("COMMENT ON COLUMN app_course_base.avg_rating IS '平均评分'");
        DB::statement("COMMENT ON COLUMN app_course_base.sort_order IS '排序'");
        DB::statement("COMMENT ON COLUMN app_course_base.is_recommend IS '是否推荐：0=否 1=是'");
        DB::statement("COMMENT ON COLUMN app_course_base.is_hot IS '是否热门：0=否 1=是'");
        DB::statement("COMMENT ON COLUMN app_course_base.is_new IS '是否新课：0=否 1=是'");
        DB::statement("COMMENT ON COLUMN app_course_base.status IS '状态：0=草稿 1=上架 2=下架'");
        DB::statement("COMMENT ON COLUMN app_course_base.publish_time IS '上架时间'");
        DB::statement("COMMENT ON COLUMN app_course_base.created_at IS '创建时间'");
        DB::statement("COMMENT ON COLUMN app_course_base.created_by IS '创建人'");
        DB::statement("COMMENT ON COLUMN app_course_base.updated_at IS '更新时间'");
        DB::statement("COMMENT ON COLUMN app_course_base.updated_by IS '更新人'");
        DB::statement("COMMENT ON COLUMN app_course_base.deleted_at IS '删除时间'");
        DB::statement("COMMENT ON COLUMN app_course_base.deleted_by IS '删除人'");

        // 索引
        DB::statement('CREATE UNIQUE INDEX uk_app_course_base_course_no_del ON app_course_base (course_no, del_flag)');
        DB::statement('CREATE INDEX idx_app_course_base_category_id ON app_course_base (category_id)');
        DB::statement('CREATE INDEX idx_app_course_base_pay_type ON app_course_base (pay_type)');
        DB::statement('CREATE INDEX idx_app_course_base_play_type ON app_course_base (play_type)');
        DB::statement('CREATE INDEX idx_app_course_base_teacher_id ON app_course_base (teacher_id)');
        DB::statement('CREATE INDEX idx_app_course_base_status ON app_course_base (status)');
        DB::statement('CREATE INDEX idx_app_course_base_list ON app_course_base (status, is_recommend, sort_order)');

        // 表注释
        DB::statement("COMMENT ON TABLE app_course_base IS '课程基础表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS app_course_base');
    }
}
