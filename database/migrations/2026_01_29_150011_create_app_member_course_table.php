<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAppMemberCourseTable extends Migration
{
    public function up()
    {
        DB::statement("
            CREATE TABLE app_member_course (
                id int8 NOT NULL GENERATED ALWAYS AS IDENTITY (INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 START 1 CACHE 1),
                member_id int8 NOT NULL,
                course_id int8 NOT NULL,
                order_no varchar(64) NULL,
                source_type int2 NOT NULL DEFAULT 1,
                promotion_id int8 NULL,
                enroll_phone varchar(20) NULL,
                enroll_age_range varchar(20) NULL,
                paid_amount numeric(10,2) NOT NULL DEFAULT 0,
                paid_points int4 NOT NULL DEFAULT 0,
                enroll_time timestamp(0) NULL,
                expire_time timestamp(0) NULL,
                is_expired int2 NOT NULL DEFAULT 0,
                learned_chapters int4 NOT NULL DEFAULT 0,
                total_chapters int4 NOT NULL DEFAULT 0,
                learned_duration int4 NOT NULL DEFAULT 0,
                progress numeric(5,2) NOT NULL DEFAULT 0,
                last_chapter_id int8 NULL,
                last_position int4 NOT NULL DEFAULT 0,
                last_learn_time timestamp(0) NULL,
                is_completed int2 NOT NULL DEFAULT 0,
                complete_time timestamp(0) NULL,
                homework_submitted int4 NOT NULL DEFAULT 0,
                homework_total int4 NOT NULL DEFAULT 0,
                checkin_days int4 NOT NULL DEFAULT 0,
                created_at timestamp(0) NULL,
                updated_at timestamp(0) NULL,
                deleted_at timestamp(0) NULL,
                PRIMARY KEY (id)
            )
        ");

        DB::statement("COMMENT ON COLUMN app_member_course.id IS 'ID'");
        DB::statement("COMMENT ON COLUMN app_member_course.member_id IS '用户ID'");
        DB::statement("COMMENT ON COLUMN app_member_course.course_id IS '课程ID'");
        DB::statement("COMMENT ON COLUMN app_member_course.order_no IS '订单号'");
        DB::statement("COMMENT ON COLUMN app_member_course.source_type IS '来源：1=购买 2=免费领取 3=兑换 4=赠送 5=活动'");
        DB::statement("COMMENT ON COLUMN app_member_course.promotion_id IS '来源推广活动ID'");
        DB::statement("COMMENT ON COLUMN app_member_course.enroll_phone IS '报名手机号'");
        DB::statement("COMMENT ON COLUMN app_member_course.enroll_age_range IS '报名年龄段'");
        DB::statement("COMMENT ON COLUMN app_member_course.paid_amount IS '实付金额'");
        DB::statement("COMMENT ON COLUMN app_member_course.paid_points IS '使用积分'");
        DB::statement("COMMENT ON COLUMN app_member_course.enroll_time IS '报名时间'");
        DB::statement("COMMENT ON COLUMN app_member_course.expire_time IS '过期时间'");
        DB::statement("COMMENT ON COLUMN app_member_course.is_expired IS '是否过期：0=否 1=是'");
        DB::statement("COMMENT ON COLUMN app_member_course.learned_chapters IS '已学章节数'");
        DB::statement("COMMENT ON COLUMN app_member_course.total_chapters IS '总章节数'");
        DB::statement("COMMENT ON COLUMN app_member_course.learned_duration IS '已学时长（秒）'");
        DB::statement("COMMENT ON COLUMN app_member_course.progress IS '学习进度%'");
        DB::statement("COMMENT ON COLUMN app_member_course.last_chapter_id IS '最后学习章节'");
        DB::statement("COMMENT ON COLUMN app_member_course.last_position IS '最后播放位置（秒）'");
        DB::statement("COMMENT ON COLUMN app_member_course.last_learn_time IS '最后学习时间'");
        DB::statement("COMMENT ON COLUMN app_member_course.is_completed IS '是否完课：0=否 1=是'");
        DB::statement("COMMENT ON COLUMN app_member_course.complete_time IS '完课时间'");
        DB::statement("COMMENT ON COLUMN app_member_course.homework_submitted IS '已提交作业数'");
        DB::statement("COMMENT ON COLUMN app_member_course.homework_total IS '总作业数'");
        DB::statement("COMMENT ON COLUMN app_member_course.checkin_days IS '打卡天数'");

        DB::statement('CREATE UNIQUE INDEX uk_app_member_course_member_course ON app_member_course (member_id, course_id)');
        DB::statement('CREATE INDEX idx_app_member_course_member_id ON app_member_course (member_id)');
        DB::statement('CREATE INDEX idx_app_member_course_course_id ON app_member_course (course_id)');
        DB::statement('CREATE INDEX idx_app_member_course_order_no ON app_member_course (order_no)');
        DB::statement('CREATE INDEX idx_app_member_course_recent ON app_member_course (member_id, is_expired, last_learn_time)');
        DB::statement("COMMENT ON TABLE app_member_course IS '用户课程表'");
    }

    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS app_member_course');
    }
}
