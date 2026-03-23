<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAppCheckinConfigTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("
            CREATE TABLE app_checkin_config (
                config_id int8 NOT NULL GENERATED ALWAYS AS IDENTITY (INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 START 1 CACHE 1),
                day_num int2 NOT NULL,
                reward_type int2 NOT NULL DEFAULT 1,
                reward_value int4 NOT NULL DEFAULT 0,
                extra_reward_value int4 NOT NULL DEFAULT 0,
                status int2 NOT NULL DEFAULT 1,
                remark varchar(200) NULL,
                create_by varchar(64) NULL,
                create_time timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP,
                update_by varchar(64) NULL,
                update_time timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP,
                del_flag int2 NOT NULL DEFAULT 0,
                PRIMARY KEY (config_id)
            )
        ");

        DB::statement("COMMENT ON COLUMN app_checkin_config.config_id IS '配置ID'");
        DB::statement("COMMENT ON COLUMN app_checkin_config.day_num IS '连续签到天数'");
        DB::statement("COMMENT ON COLUMN app_checkin_config.reward_type IS '奖励类型：1积分 2经验值'");
        DB::statement("COMMENT ON COLUMN app_checkin_config.reward_value IS '奖励数值'");
        DB::statement("COMMENT ON COLUMN app_checkin_config.extra_reward_value IS '额外奖励数值'");
        DB::statement("COMMENT ON COLUMN app_checkin_config.status IS '状态：1启用 2禁用'");
        DB::statement("COMMENT ON COLUMN app_checkin_config.remark IS '备注说明'");
        DB::statement("COMMENT ON COLUMN app_checkin_config.create_by IS '创建者'");
        DB::statement("COMMENT ON COLUMN app_checkin_config.create_time IS '创建时间'");
        DB::statement("COMMENT ON COLUMN app_checkin_config.update_by IS '更新者'");
        DB::statement("COMMENT ON COLUMN app_checkin_config.update_time IS '更新时间'");
        DB::statement("COMMENT ON COLUMN app_checkin_config.del_flag IS '删除标志：0正常 1删除'");

        DB::statement('CREATE UNIQUE INDEX uk_app_checkin_config_day_num_reward_type_del_flag ON app_checkin_config (day_num, reward_type, del_flag)');

        DB::statement("COMMENT ON TABLE app_checkin_config IS '签到奖励配置表'");

        // 初始化7天签到奖励配置
        DB::table('app_checkin_config')->insert([
            ['day_num' => 1, 'reward_type' => 1, 'reward_value' => 10, 'extra_reward_value' => 0, 'remark' => '第1天签到奖励'],
            ['day_num' => 2, 'reward_type' => 1, 'reward_value' => 15, 'extra_reward_value' => 0, 'remark' => '第2天签到奖励'],
            ['day_num' => 3, 'reward_type' => 1, 'reward_value' => 20, 'extra_reward_value' => 0, 'remark' => '第3天签到奖励'],
            ['day_num' => 4, 'reward_type' => 1, 'reward_value' => 25, 'extra_reward_value' => 0, 'remark' => '第4天签到奖励'],
            ['day_num' => 5, 'reward_type' => 1, 'reward_value' => 30, 'extra_reward_value' => 0, 'remark' => '第5天签到奖励'],
            ['day_num' => 6, 'reward_type' => 1, 'reward_value' => 40, 'extra_reward_value' => 0, 'remark' => '第6天签到奖励'],
            ['day_num' => 7, 'reward_type' => 1, 'reward_value' => 50, 'extra_reward_value' => 50, 'remark' => '第7天签到奖励，额外奖励50'],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS app_checkin_config');
    }
}
