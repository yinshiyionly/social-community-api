<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAppMemberLearningCheckinTable extends Migration
{
    public function up()
    {
        DB::statement("
            CREATE TABLE app_member_learning_checkin (
                id int8 NOT NULL GENERATED ALWAYS AS IDENTITY (INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 START 1 CACHE 1),
                member_id int8 NOT NULL,
                course_id int8 NOT NULL,
                chapter_id int8 NULL,
                checkin_date date NOT NULL,
                learn_duration int4 NOT NULL DEFAULT 0,
                chapters_learned int4 NOT NULL DEFAULT 0,
                summary text NULL,
                images jsonb NOT NULL DEFAULT '[]',
                point_earned int4 NOT NULL DEFAULT 0,
                created_at timestamp(0) NULL,
                PRIMARY KEY (id)
            )
        ");

        DB::statement("COMMENT ON COLUMN app_member_learning_checkin.id IS 'ID'");
        DB::statement("COMMENT ON COLUMN app_member_learning_checkin.member_id IS '用户ID'");
        DB::statement("COMMENT ON COLUMN app_member_learning_checkin.course_id IS '课程ID'");
        DB::statement("COMMENT ON COLUMN app_member_learning_checkin.chapter_id IS '章节ID'");
        DB::statement("COMMENT ON COLUMN app_member_learning_checkin.checkin_date IS '打卡日期'");
        DB::statement("COMMENT ON COLUMN app_member_learning_checkin.learn_duration IS '学习时长（秒）'");
        DB::statement("COMMENT ON COLUMN app_member_learning_checkin.chapters_learned IS '学习章节数'");
        DB::statement("COMMENT ON COLUMN app_member_learning_checkin.summary IS '学习总结'");
        DB::statement("COMMENT ON COLUMN app_member_learning_checkin.images IS '打卡图片'");
        DB::statement("COMMENT ON COLUMN app_member_learning_checkin.point_earned IS '获得积分'");

        DB::statement('CREATE UNIQUE INDEX uk_app_member_learning_checkin_member_course_date ON app_member_learning_checkin (member_id, course_id, checkin_date)');
        DB::statement('CREATE INDEX idx_app_member_learning_checkin_member_id ON app_member_learning_checkin (member_id)');
        DB::statement('CREATE INDEX idx_app_member_learning_checkin_date ON app_member_learning_checkin (checkin_date)');
        DB::statement("COMMENT ON TABLE app_member_learning_checkin IS '学习打卡记录表'");
    }

    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS app_member_learning_checkin');
    }
}
