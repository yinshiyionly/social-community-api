<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAppConfigTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement(<<<'SQL'
            CREATE TABLE app_config (
                config_id int8 NOT NULL GENERATED ALWAYS AS IDENTITY (INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 START 1 CACHE 1),
                config_key text NOT NULL DEFAULT '',
                config_name text NOT NULL DEFAULT '',
                config_type text NOT NULL DEFAULT 'json',
                group_name text NOT NULL DEFAULT 'default',
                config_value jsonb NOT NULL DEFAULT '{}'::jsonb,
                visibility_rule jsonb NOT NULL DEFAULT '{"mode":"always","timezone":"Asia/Shanghai","windows":[]}'::jsonb,
                is_enabled boolean NOT NULL DEFAULT true,
                sort_num int4 NOT NULL DEFAULT 0,
                env text NOT NULL DEFAULT 'prod',
                platform text NOT NULL DEFAULT 'all',
                description text NOT NULL DEFAULT '',
                created_at timestamptz NOT NULL DEFAULT now(),
                updated_at timestamptz NOT NULL DEFAULT now(),
                PRIMARY KEY (config_id),
                CONSTRAINT uk_app_config_config_key_env_platform UNIQUE (config_key, env, platform),
                CONSTRAINT ck_app_config_key_nonempty CHECK (config_key <> ''),
                CONSTRAINT ck_app_config_name_nonempty CHECK (config_name <> ''),
                CONSTRAINT ck_app_config_key_len CHECK (char_length(config_key) <= 100),
                CONSTRAINT ck_app_config_name_len CHECK (char_length(config_name) <= 100),
                CONSTRAINT ck_app_config_type_len CHECK (char_length(config_type) <= 50),
                CONSTRAINT ck_app_config_group_len CHECK (char_length(group_name) <= 50),
                CONSTRAINT ck_app_config_env_len CHECK (char_length(env) <= 20),
                CONSTRAINT ck_app_config_platform_len CHECK (char_length(platform) <= 20),
                CONSTRAINT ck_app_config_sort_num_nonneg CHECK (sort_num >= 0),
                CONSTRAINT ck_app_config_type_enum CHECK (config_type IN ('bool', 'number', 'string', 'json', 'array')),
                CONSTRAINT ck_app_config_value_type CHECK (jsonb_typeof(config_value) IN ('object', 'array', 'string', 'number', 'boolean', 'null')),
                CONSTRAINT ck_app_config_visibility_rule_object CHECK (jsonb_typeof(visibility_rule) = 'object'),
                CONSTRAINT ck_app_config_visibility_rule_mode CHECK (
                    visibility_rule ? 'mode'
                    AND visibility_rule->>'mode' IN ('always', 'window')
                ),
                CONSTRAINT ck_app_config_visibility_rule_windows CHECK (
                    visibility_rule ? 'windows'
                    AND jsonb_typeof(visibility_rule->'windows') = 'array'
                ),
                CONSTRAINT ck_app_config_visibility_rule_timezone CHECK (
                    visibility_rule ? 'timezone'
                    AND jsonb_typeof(visibility_rule->'timezone') = 'string'
                )
            )
        SQL
        );

        // 列注释
        DB::statement("COMMENT ON COLUMN app_config.config_id IS '配置ID'");
        DB::statement("COMMENT ON COLUMN app_config.config_key IS '配置键，程序读取的唯一业务标识'");
        DB::statement("COMMENT ON COLUMN app_config.config_name IS '配置名称，供管理端展示'");
        DB::statement("COMMENT ON COLUMN app_config.config_type IS '配置值类型：bool/number/string/json/array'");
        DB::statement("COMMENT ON COLUMN app_config.group_name IS '配置分组名称，用于后台归类筛选'");
        DB::statement("COMMENT ON COLUMN app_config.config_value IS '配置值，使用JSONB存储'");
        DB::statement("COMMENT ON COLUMN app_config.visibility_rule IS '显隐规则：mode=always/window，windows为时间段数组'");
        DB::statement("COMMENT ON COLUMN app_config.is_enabled IS '是否启用：true启用 false禁用'");
        DB::statement("COMMENT ON COLUMN app_config.sort_num IS '排序值，越大越靠前'");
        DB::statement("COMMENT ON COLUMN app_config.env IS '环境标识：prod/staging/dev等'");
        DB::statement("COMMENT ON COLUMN app_config.platform IS '平台标识：all/ios/android等'");
        DB::statement("COMMENT ON COLUMN app_config.description IS '配置描述'");
        DB::statement("COMMENT ON COLUMN app_config.created_at IS '创建时间'");
        DB::statement("COMMENT ON COLUMN app_config.updated_at IS '更新时间'");

        // 索引
        DB::statement('CREATE INDEX idx_app_config_env_platform_enabled_sort ON app_config (env, platform, is_enabled, sort_num DESC, config_id DESC)');
        DB::statement('CREATE INDEX idx_app_config_group_name_sort ON app_config (group_name, sort_num DESC, config_id DESC)');

        // 表注释
        DB::statement("COMMENT ON TABLE app_config IS 'App模块配置表（支持启用状态与时间段显隐规则）'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS app_config');
    }
}
