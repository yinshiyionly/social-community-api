<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAppCertificateTemplateTable extends Migration
{
    public function up()
    {
        DB::statement("
            CREATE TABLE app_certificate_template (
                template_id int8 NOT NULL GENERATED ALWAYS AS IDENTITY (INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 START 1 CACHE 1),
                template_name varchar(100) NOT NULL DEFAULT '',
                template_image varchar(500) NOT NULL DEFAULT '',
                template_config jsonb NOT NULL DEFAULT '{}',
                status int2 NOT NULL DEFAULT 1,
                create_time timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP,
                update_time timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (template_id)
            )
        ");

        DB::statement("COMMENT ON COLUMN app_certificate_template.template_id IS '模板ID'");
        DB::statement("COMMENT ON COLUMN app_certificate_template.template_name IS '模板名称'");
        DB::statement("COMMENT ON COLUMN app_certificate_template.template_image IS '模板背景图'");
        DB::statement("COMMENT ON COLUMN app_certificate_template.template_config IS '模板配置（文字位置、字体等）'");
        DB::statement("COMMENT ON COLUMN app_certificate_template.status IS '状态：1=启用 2=禁用'");
        DB::statement("COMMENT ON TABLE app_certificate_template IS '证书模板表'");
    }

    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS app_certificate_template');
    }
}
