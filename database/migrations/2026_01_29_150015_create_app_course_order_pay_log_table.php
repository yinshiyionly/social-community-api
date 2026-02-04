<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAppCourseOrderPayLogTable extends Migration
{
    public function up()
    {
        DB::statement("
            CREATE TABLE app_course_order_pay_log (
                log_id int8 NOT NULL GENERATED ALWAYS AS IDENTITY (INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 START 1 CACHE 1),
                order_no varchar(64) NOT NULL,
                member_id int8 NOT NULL,
                pay_type int2 NOT NULL,
                pay_amount numeric(10,2) NOT NULL DEFAULT 0,
                trade_no varchar(100) NULL,
                pay_result int2 NOT NULL DEFAULT 0,
                pay_response text NULL,
                client_ip varchar(50) NULL,
                created_at timestamp(0) NULL,
                PRIMARY KEY (log_id)
            )
        ");

        DB::statement("COMMENT ON COLUMN app_course_order_pay_log.log_id IS '日志ID'");
        DB::statement("COMMENT ON COLUMN app_course_order_pay_log.order_no IS '订单号'");
        DB::statement("COMMENT ON COLUMN app_course_order_pay_log.member_id IS '用户ID'");
        DB::statement("COMMENT ON COLUMN app_course_order_pay_log.pay_type IS '支付方式'");
        DB::statement("COMMENT ON COLUMN app_course_order_pay_log.pay_amount IS '支付金额'");
        DB::statement("COMMENT ON COLUMN app_course_order_pay_log.trade_no IS '第三方流水号'");
        DB::statement("COMMENT ON COLUMN app_course_order_pay_log.pay_result IS '支付结果：0=失败 1=成功'");
        DB::statement("COMMENT ON COLUMN app_course_order_pay_log.pay_response IS '支付响应'");

        DB::statement('CREATE INDEX idx_app_course_order_pay_log_order_no ON app_course_order_pay_log (order_no)');
        DB::statement('CREATE INDEX idx_app_course_order_pay_log_member_id ON app_course_order_pay_log (member_id)');
        DB::statement("COMMENT ON TABLE app_course_order_pay_log IS '订单支付日志表'");
    }

    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS app_course_order_pay_log');
    }
}
