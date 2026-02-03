<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAppCourseOrderTable extends Migration
{
    public function up()
    {
        DB::statement("
            CREATE TABLE app_course_order (
                order_id int8 NOT NULL GENERATED ALWAYS AS IDENTITY (INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 START 100000000001 CACHE 1),
                order_no varchar(64) NOT NULL,
                member_id int8 NOT NULL,
                course_id int8 NOT NULL,
                course_title varchar(200) NOT NULL DEFAULT '',
                course_cover varchar(500) NULL,
                original_price numeric(10,2) NOT NULL DEFAULT 0,
                current_price numeric(10,2) NOT NULL DEFAULT 0,
                discount_amount numeric(10,2) NOT NULL DEFAULT 0,
                coupon_amount numeric(10,2) NOT NULL DEFAULT 0,
                point_deduct int4 NOT NULL DEFAULT 0,
                point_amount numeric(10,2) NOT NULL DEFAULT 0,
                paid_amount numeric(10,2) NOT NULL DEFAULT 0,
                coupon_id varchar(64) NULL,
                promotion_type varchar(50) NULL,
                promotion_id varchar(64) NULL,
                pay_status int2 NOT NULL DEFAULT 0,
                pay_type int2 NULL,
                pay_trade_no varchar(100) NULL,
                pay_time timestamp(0) NULL,
                expire_time timestamp(0) NULL,
                refund_status int2 NOT NULL DEFAULT 0,
                refund_amount numeric(10,2) NOT NULL DEFAULT 0,
                refund_reason varchar(500) NULL,
                refund_time timestamp(0) NULL,
                inviter_id int8 NULL,
                commission_amount numeric(10,2) NOT NULL DEFAULT 0,
                commission_status int2 NOT NULL DEFAULT 0,
                remark varchar(500) NULL,
                client_ip varchar(50) NULL,
                user_agent varchar(500) NULL,
                create_time timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP,
                update_time timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (order_id)
            )
        ");

        DB::statement("COMMENT ON COLUMN app_course_order.order_id IS '订单ID'");
        DB::statement("COMMENT ON COLUMN app_course_order.order_no IS '订单号'");
        DB::statement("COMMENT ON COLUMN app_course_order.member_id IS '用户ID'");
        DB::statement("COMMENT ON COLUMN app_course_order.course_id IS '课程ID'");
        DB::statement("COMMENT ON COLUMN app_course_order.course_title IS '课程标题（快照）'");
        DB::statement("COMMENT ON COLUMN app_course_order.course_cover IS '课程封面（快照）'");
        DB::statement("COMMENT ON COLUMN app_course_order.original_price IS '原价'");
        DB::statement("COMMENT ON COLUMN app_course_order.current_price IS '现价'");
        DB::statement("COMMENT ON COLUMN app_course_order.discount_amount IS '优惠金额'");
        DB::statement("COMMENT ON COLUMN app_course_order.coupon_amount IS '优惠券抵扣'");
        DB::statement("COMMENT ON COLUMN app_course_order.point_deduct IS '积分抵扣数'");
        DB::statement("COMMENT ON COLUMN app_course_order.point_amount IS '积分抵扣金额'");
        DB::statement("COMMENT ON COLUMN app_course_order.paid_amount IS '实付金额'");
        DB::statement("COMMENT ON COLUMN app_course_order.coupon_id IS '优惠券ID'");
        DB::statement("COMMENT ON COLUMN app_course_order.promotion_type IS '促销类型：seckill/discount/group'");
        DB::statement("COMMENT ON COLUMN app_course_order.promotion_id IS '促销活动ID'");
        DB::statement("COMMENT ON COLUMN app_course_order.pay_status IS '支付状态：0=待支付 1=已支付 2=已退款 3=已关闭'");
        DB::statement("COMMENT ON COLUMN app_course_order.pay_type IS '支付方式：1=微信 2=支付宝 3=余额 4=免费'");
        DB::statement("COMMENT ON COLUMN app_course_order.pay_trade_no IS '支付流水号'");
        DB::statement("COMMENT ON COLUMN app_course_order.pay_time IS '支付时间'");
        DB::statement("COMMENT ON COLUMN app_course_order.expire_time IS '订单过期时间'");
        DB::statement("COMMENT ON COLUMN app_course_order.refund_status IS '退款状态：0=无 1=申请中 2=已退款 3=已拒绝'");
        DB::statement("COMMENT ON COLUMN app_course_order.refund_amount IS '退款金额'");
        DB::statement("COMMENT ON COLUMN app_course_order.refund_reason IS '退款原因'");
        DB::statement("COMMENT ON COLUMN app_course_order.refund_time IS '退款时间'");
        DB::statement("COMMENT ON COLUMN app_course_order.inviter_id IS '邀请人ID'");
        DB::statement("COMMENT ON COLUMN app_course_order.commission_amount IS '佣金金额'");
        DB::statement("COMMENT ON COLUMN app_course_order.commission_status IS '佣金状态：0=待结算 1=已结算'");
        DB::statement("COMMENT ON COLUMN app_course_order.remark IS '备注'");

        DB::statement('CREATE UNIQUE INDEX uk_app_course_order_order_no ON app_course_order (order_no)');
        DB::statement('CREATE INDEX idx_app_course_order_member_id ON app_course_order (member_id)');
        DB::statement('CREATE INDEX idx_app_course_order_course_id ON app_course_order (course_id)');
        DB::statement('CREATE INDEX idx_app_course_order_pay_status ON app_course_order (pay_status)');
        DB::statement('CREATE INDEX idx_app_course_order_create_time ON app_course_order (create_time)');
        DB::statement('CREATE INDEX idx_app_course_order_member_status ON app_course_order (member_id, pay_status)');
        DB::statement("COMMENT ON TABLE app_course_order IS '课程订单表'");
    }

    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS app_course_order');
    }
}
