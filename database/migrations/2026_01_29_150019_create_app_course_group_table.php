<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAppCourseGroupTable extends Migration
{
    public function up()
    {
        DB::statement("
            CREATE TABLE app_course_group (
                group_id int8 NOT NULL GENERATED ALWAYS AS IDENTITY (INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 START 1 CACHE 1),
                course_id int8 NOT NULL,
                leader_id int8 NOT NULL,
                order_no varchar(64) NOT NULL,
                group_size int4 NOT NULL DEFAULT 2,
                current_size int4 NOT NULL DEFAULT 1,
                group_price numeric(10,2) NOT NULL DEFAULT 0,
                status int2 NOT NULL DEFAULT 0,
                expire_time timestamp(0) NULL,
                success_time timestamp(0) NULL,
                create_time timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP,
                update_time timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (group_id)
            )
        ");

        DB::statement("COMMENT ON COLUMN app_course_group.group_id IS '拼团ID'");
        DB::statement("COMMENT ON COLUMN app_course_group.course_id IS '课程ID'");
        DB::statement("COMMENT ON COLUMN app_course_group.leader_id IS '团长用户ID'");
        DB::statement("COMMENT ON COLUMN app_course_group.order_no IS '团长订单号'");
        DB::statement("COMMENT ON COLUMN app_course_group.group_size IS '成团人数'");
        DB::statement("COMMENT ON COLUMN app_course_group.current_size IS '当前人数'");
        DB::statement("COMMENT ON COLUMN app_course_group.group_price IS '拼团价格'");
        DB::statement("COMMENT ON COLUMN app_course_group.status IS '状态：0=拼团中 1=已成团 2=已失败 3=已取消'");
        DB::statement("COMMENT ON COLUMN app_course_group.expire_time IS '过期时间'");
        DB::statement("COMMENT ON COLUMN app_course_group.success_time IS '成团时间'");

        DB::statement('CREATE INDEX idx_app_course_group_course_id ON app_course_group (course_id)');
        DB::statement('CREATE INDEX idx_app_course_group_leader_id ON app_course_group (leader_id)');
        DB::statement('CREATE INDEX idx_app_course_group_status ON app_course_group (status)');
        DB::statement('CREATE INDEX idx_app_course_group_course_status ON app_course_group (course_id, status)');
        DB::statement("COMMENT ON TABLE app_course_group IS '课程拼团表'");
    }

    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS app_course_group');
    }
}
