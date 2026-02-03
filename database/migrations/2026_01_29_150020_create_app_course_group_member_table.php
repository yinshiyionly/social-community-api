<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAppCourseGroupMemberTable extends Migration
{
    public function up()
    {
        DB::statement("
            CREATE TABLE app_course_group_member (
                id int8 NOT NULL GENERATED ALWAYS AS IDENTITY (INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 START 1 CACHE 1),
                group_id int8 NOT NULL,
                member_id int8 NOT NULL,
                order_no varchar(64) NOT NULL,
                is_leader int2 NOT NULL DEFAULT 0,
                status int2 NOT NULL DEFAULT 0,
                join_time timestamp(0) NULL,
                create_time timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            )
        ");

        DB::statement("COMMENT ON COLUMN app_course_group_member.id IS 'ID'");
        DB::statement("COMMENT ON COLUMN app_course_group_member.group_id IS '拼团ID'");
        DB::statement("COMMENT ON COLUMN app_course_group_member.member_id IS '用户ID'");
        DB::statement("COMMENT ON COLUMN app_course_group_member.order_no IS '订单号'");
        DB::statement("COMMENT ON COLUMN app_course_group_member.is_leader IS '是否团长：0=否 1=是'");
        DB::statement("COMMENT ON COLUMN app_course_group_member.status IS '状态：0=待支付 1=已支付 2=已退款'");
        DB::statement("COMMENT ON COLUMN app_course_group_member.join_time IS '加入时间'");

        DB::statement('CREATE UNIQUE INDEX uk_app_course_group_member_group_member ON app_course_group_member (group_id, member_id)');
        DB::statement('CREATE INDEX idx_app_course_group_member_member_id ON app_course_group_member (member_id)');
        DB::statement('CREATE INDEX idx_app_course_group_member_order_no ON app_course_group_member (order_no)');
        DB::statement("COMMENT ON TABLE app_course_group_member IS '拼团成员表'");
    }

    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS app_course_group_member');
    }
}
