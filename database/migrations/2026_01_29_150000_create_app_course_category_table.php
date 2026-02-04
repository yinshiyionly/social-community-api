<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAppCourseCategoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("
            CREATE TABLE app_course_category (
                category_id int4 NOT NULL GENERATED ALWAYS AS IDENTITY (INCREMENT 1 MINVALUE 1 MAXVALUE 2147483647 START 1 CACHE 1),
                parent_id int4 NOT NULL DEFAULT 0,
                category_name varchar(50) NOT NULL DEFAULT '',
                category_code varchar(50) NOT NULL DEFAULT '',
                icon varchar(255) NULL,
                cover varchar(255) NULL,
                description varchar(500) NULL,
                sort_order int4 NOT NULL DEFAULT 0,
                status int2 NOT NULL DEFAULT 1,
                created_at timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP,
                created_by int8 NULL,
                updated_at timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP,
                updated_by int8 NULL,
                deleted_at timestamp(0) NULL,
                deleted_by int8 NULL,
                PRIMARY KEY (category_id)
            )
        ");

        // 列注释
        DB::statement("COMMENT ON COLUMN app_course_category.category_id IS '分类ID'");
        DB::statement("COMMENT ON COLUMN app_course_category.parent_id IS '父分类ID'");
        DB::statement("COMMENT ON COLUMN app_course_category.category_name IS '分类名称'");
        DB::statement("COMMENT ON COLUMN app_course_category.category_code IS '分类编码：yoga/tea/calligraphy等'");
        DB::statement("COMMENT ON COLUMN app_course_category.icon IS '分类图标'");
        DB::statement("COMMENT ON COLUMN app_course_category.cover IS '分类封面'");
        DB::statement("COMMENT ON COLUMN app_course_category.description IS '分类描述'");
        DB::statement("COMMENT ON COLUMN app_course_category.sort_order IS '排序'");
        DB::statement("COMMENT ON COLUMN app_course_category.status IS '状态：1=启用 2=禁用'");
        DB::statement("COMMENT ON COLUMN app_course_category.created_at IS '创建时间'");
        DB::statement("COMMENT ON COLUMN app_course_category.created_by IS '创建人'");
        DB::statement("COMMENT ON COLUMN app_course_category.updated_at IS '更新时间'");
        DB::statement("COMMENT ON COLUMN app_course_category.updated_by IS '更新人'");
        DB::statement("COMMENT ON COLUMN app_course_category.deleted_at IS '删除时间'");
        DB::statement("COMMENT ON COLUMN app_course_category.deleted_by IS '删除人'");

        // 索引
        DB::statement('CREATE UNIQUE INDEX uk_app_course_category_code_del ON app_course_category (category_code, del_flag)');
        DB::statement('CREATE INDEX idx_app_course_category_parent_id ON app_course_category (parent_id)');
        DB::statement('CREATE INDEX idx_app_course_category_status ON app_course_category (status)');

        // 表注释
        DB::statement("COMMENT ON TABLE app_course_category IS '课程分类表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS app_course_category');
    }
}
