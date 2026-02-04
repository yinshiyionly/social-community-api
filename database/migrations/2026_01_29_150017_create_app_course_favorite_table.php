<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAppCourseFavoriteTable extends Migration
{
    public function up()
    {
        DB::statement("
            CREATE TABLE app_course_favorite (
                id int8 NOT NULL GENERATED ALWAYS AS IDENTITY (INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 START 1 CACHE 1),
                member_id int8 NOT NULL,
                course_id int8 NOT NULL,
                created_at timestamp(0) NULL,
                PRIMARY KEY (id)
            )
        ");

        DB::statement("COMMENT ON COLUMN app_course_favorite.id IS 'ID'");
        DB::statement("COMMENT ON COLUMN app_course_favorite.member_id IS '用户ID'");
        DB::statement("COMMENT ON COLUMN app_course_favorite.course_id IS '课程ID'");

        DB::statement('CREATE UNIQUE INDEX uk_app_course_favorite_member_course ON app_course_favorite (member_id, course_id)');
        DB::statement('CREATE INDEX idx_app_course_favorite_member_id ON app_course_favorite (member_id)');
        DB::statement('CREATE INDEX idx_app_course_favorite_course_id ON app_course_favorite (course_id)');
        DB::statement("COMMENT ON TABLE app_course_favorite IS '课程收藏表'");
    }

    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS app_course_favorite');
    }
}
