<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAppMemberPointLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("
            CREATE TABLE app_member_point_log (
                log_id int8 NOT NULL GENERATED ALWAYS AS IDENTITY (INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 START 1 CACHE 1),
                member_id int8 NOT NULL,
                change_type int2 NOT NULL,
                change_value int4 NOT NULL DEFAULT 0,
                before_points int8 NOT NULL DEFAULT 0,
                after_points int8 NOT NULL DEFAULT 0,
                source_type int2 NOT NULL,
                source_id varchar(64) NULL,
                task_code varchar(50) NULL,
                order_no varchar(64) NULL,
                title varchar(200) NOT NULL DEFAULT '',
                remark varchar(500) NULL,
                operator_id int8 NULL,
                operator_name varchar(64) NULL,
                expire_time timestamp(0) NULL,
                client_ip varchar(50) NULL,
                create_time timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (log_id)
            )
        ");

        // 列注释
        DB::statement("COMMENT ON COLUMN app_member_point_log.log_id IS '日志ID'");
        DB::statement("COMMENT ON COLUMN app_member_point_log.member_id IS '用户ID'");
        DB::statement("COMMENT ON COLUMN app_member_point_log.change_type IS '变动类型：1获取 2消费 3冻结 4解冻 5过期 6后台调整'");
        DB::statement("COMMENT ON COLUMN app_member_point_log.change_value IS '变动积分值'");
        DB::statement("COMMENT ON COLUMN app_member_point_log.before_points IS '变动前可用积分'");
        DB::statement("COMMENT ON COLUMN app_member_point_log.after_points IS '变动后可用积分'");
        DB::statement("COMMENT ON COLUMN app_member_point_log.source_type IS '来源类型：1任务奖励 2消费抵扣 3订单退款 4后台赠送 5后台扣除 6过期清零 7活动奖励'");
        DB::statement("COMMENT ON COLUMN app_member_point_log.source_id IS '来源ID'");
        DB::statement("COMMENT ON COLUMN app_member_point_log.task_code IS '关联任务编码'");
        DB::statement("COMMENT ON COLUMN app_member_point_log.order_no IS '关联订单号'");
        DB::statement("COMMENT ON COLUMN app_member_point_log.title IS '流水标题'");
        DB::statement("COMMENT ON COLUMN app_member_point_log.remark IS '备注说明'");
        DB::statement("COMMENT ON COLUMN app_member_point_log.operator_id IS '操作人ID'");
        DB::statement("COMMENT ON COLUMN app_member_point_log.operator_name IS '操作人名称'");
        DB::statement("COMMENT ON COLUMN app_member_point_log.expire_time IS '积分过期时间'");
        DB::statement("COMMENT ON COLUMN app_member_point_log.client_ip IS '客户端IP'");
        DB::statement("COMMENT ON COLUMN app_member_point_log.create_time IS '创建时间'");

        // 索引
        DB::statement('CREATE INDEX idx_app_member_point_log_member_id ON app_member_point_log (member_id)');
        DB::statement('CREATE INDEX idx_app_member_point_log_change_type ON app_member_point_log (change_type)');
        DB::statement('CREATE INDEX idx_app_member_point_log_source_type ON app_member_point_log (source_type)');
        DB::statement('CREATE INDEX idx_app_member_point_log_task_code ON app_member_point_log (task_code)');
        DB::statement('CREATE INDEX idx_app_member_point_log_create_time ON app_member_point_log (create_time)');
        DB::statement('CREATE INDEX idx_app_member_point_log_member_time ON app_member_point_log (member_id, create_time)');

        // 表注释
        DB::statement("COMMENT ON TABLE app_member_point_log IS '用户积分流水日志表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS app_member_point_log');
    }
}
