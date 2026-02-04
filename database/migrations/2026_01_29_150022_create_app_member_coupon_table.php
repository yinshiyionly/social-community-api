<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAppMemberCouponTable extends Migration
{
    public function up()
    {
        DB::statement("
            CREATE TABLE app_member_coupon (
                id int8 NOT NULL GENERATED ALWAYS AS IDENTITY (INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 START 1 CACHE 1),
                coupon_id int8 NOT NULL,
                member_id int8 NOT NULL,
                coupon_code varchar(32) NULL,
                status int2 NOT NULL DEFAULT 0,
                receive_time timestamp(0) NULL,
                expire_time timestamp(0) NULL,
                use_time timestamp(0) NULL,
                use_order_no varchar(64) NULL,
                created_at timestamp(0) NULL,
                updated_at timestamp(0) NULL,
                deleted_at timestamp(0) NULL,
                PRIMARY KEY (id)
            )
        ");

        DB::statement("COMMENT ON COLUMN app_member_coupon.id IS 'ID'");
        DB::statement("COMMENT ON COLUMN app_member_coupon.coupon_id IS '优惠券模板ID'");
        DB::statement("COMMENT ON COLUMN app_member_coupon.member_id IS '用户ID'");
        DB::statement("COMMENT ON COLUMN app_member_coupon.coupon_code IS '优惠券码'");
        DB::statement("COMMENT ON COLUMN app_member_coupon.status IS '状态：0=未使用 1=已使用 2=已过期'");
        DB::statement("COMMENT ON COLUMN app_member_coupon.receive_time IS '领取时间'");
        DB::statement("COMMENT ON COLUMN app_member_coupon.expire_time IS '过期时间'");
        DB::statement("COMMENT ON COLUMN app_member_coupon.use_time IS '使用时间'");
        DB::statement("COMMENT ON COLUMN app_member_coupon.use_order_no IS '使用订单号'");

        DB::statement('CREATE INDEX idx_app_member_coupon_member_id ON app_member_coupon (member_id)');
        DB::statement('CREATE INDEX idx_app_member_coupon_coupon_id ON app_member_coupon (coupon_id)');
        DB::statement('CREATE INDEX idx_app_member_coupon_member_status ON app_member_coupon (member_id, status)');
        DB::statement('CREATE INDEX idx_app_member_coupon_code ON app_member_coupon (coupon_code)');
        DB::statement("COMMENT ON TABLE app_member_coupon IS '用户优惠券表'");
    }

    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS app_member_coupon');
    }
}
