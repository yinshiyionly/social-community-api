<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAppCouponTemplateTable extends Migration
{
    public function up()
    {
        DB::statement("
            CREATE TABLE app_coupon_template (
                coupon_id int8 NOT NULL GENERATED ALWAYS AS IDENTITY (INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 START 1 CACHE 1),
                coupon_name varchar(100) NOT NULL DEFAULT '',
                coupon_type int2 NOT NULL DEFAULT 1,
                threshold_amount numeric(10,2) NOT NULL DEFAULT 0,
                discount_amount numeric(10,2) NOT NULL DEFAULT 0,
                discount_rate numeric(5,2) NOT NULL DEFAULT 0,
                max_discount numeric(10,2) NOT NULL DEFAULT 0,
                scope_type int2 NOT NULL DEFAULT 1,
                scope_ids jsonb NOT NULL DEFAULT '[]',
                total_count int4 NOT NULL DEFAULT 0,
                issued_count int4 NOT NULL DEFAULT 0,
                used_count int4 NOT NULL DEFAULT 0,
                per_limit int4 NOT NULL DEFAULT 1,
                valid_type int2 NOT NULL DEFAULT 1,
                valid_start_time timestamp(0) NULL,
                valid_end_time timestamp(0) NULL,
                valid_days int4 NOT NULL DEFAULT 0,
                receive_type int2 NOT NULL DEFAULT 1,
                receive_start_time timestamp(0) NULL,
                receive_end_time timestamp(0) NULL,
                sort_order int4 NOT NULL DEFAULT 0,
                status int2 NOT NULL DEFAULT 1,
                created_at timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP,
                created_by int8 NULL,
                updated_at timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP,
                updated_by int8 NULL,
                deleted_at timestamp(0) NULL,
                deleted_by int8 NULL,
                PRIMARY KEY (coupon_id)
            )
        ");

        DB::statement("COMMENT ON COLUMN app_coupon_template.coupon_id IS '优惠券ID'");
        DB::statement("COMMENT ON COLUMN app_coupon_template.coupon_name IS '优惠券名称'");
        DB::statement("COMMENT ON COLUMN app_coupon_template.coupon_type IS '类型：1=满减券 2=折扣券 3=无门槛券'");
        DB::statement("COMMENT ON COLUMN app_coupon_template.threshold_amount IS '使用门槛金额'");
        DB::statement("COMMENT ON COLUMN app_coupon_template.discount_amount IS '优惠金额'");
        DB::statement("COMMENT ON COLUMN app_coupon_template.discount_rate IS '折扣率（0.01-1）'");
        DB::statement("COMMENT ON COLUMN app_coupon_template.max_discount IS '最大优惠金额'");
        DB::statement("COMMENT ON COLUMN app_coupon_template.scope_type IS '适用范围：1=全部课程 2=指定分类 3=指定课程'");
        DB::statement("COMMENT ON COLUMN app_coupon_template.scope_ids IS '适用ID列表'");
        DB::statement("COMMENT ON COLUMN app_coupon_template.total_count IS '发放总量（0=不限）'");
        DB::statement("COMMENT ON COLUMN app_coupon_template.issued_count IS '已发放数量'");
        DB::statement("COMMENT ON COLUMN app_coupon_template.used_count IS '已使用数量'");
        DB::statement("COMMENT ON COLUMN app_coupon_template.per_limit IS '每人限领数量'");
        DB::statement("COMMENT ON COLUMN app_coupon_template.valid_type IS '有效期类型：1=固定时间 2=领取后N天'");
        DB::statement("COMMENT ON COLUMN app_coupon_template.valid_start_time IS '有效开始时间'");
        DB::statement("COMMENT ON COLUMN app_coupon_template.valid_end_time IS '有效结束时间'");
        DB::statement("COMMENT ON COLUMN app_coupon_template.valid_days IS '领取后有效天数'");
        DB::statement("COMMENT ON COLUMN app_coupon_template.receive_type IS '领取方式：1=公开领取 2=系统发放 3=兑换码'");
        DB::statement("COMMENT ON COLUMN app_coupon_template.receive_start_time IS '领取开始时间'");
        DB::statement("COMMENT ON COLUMN app_coupon_template.receive_end_time IS '领取结束时间'");
        DB::statement("COMMENT ON COLUMN app_coupon_template.sort_order IS '排序'");
        DB::statement("COMMENT ON COLUMN app_coupon_template.status IS '状态：1=启用 2=禁用'");
        DB::statement("COMMENT ON COLUMN app_coupon_template.created_at IS '创建时间'");
        DB::statement("COMMENT ON COLUMN app_coupon_template.created_by IS '创建人'");
        DB::statement("COMMENT ON COLUMN app_coupon_template.updated_at IS '更新时间'");
        DB::statement("COMMENT ON COLUMN app_coupon_template.updated_by IS '更新人'");
        DB::statement("COMMENT ON COLUMN app_coupon_template.deleted_at IS '删除时间'");
        DB::statement("COMMENT ON COLUMN app_coupon_template.deleted_by IS '删除人'");

        DB::statement('CREATE INDEX idx_app_coupon_template_status ON app_coupon_template (status)');
        DB::statement('CREATE INDEX idx_app_coupon_template_coupon_type ON app_coupon_template (coupon_type)');
        DB::statement("COMMENT ON TABLE app_coupon_template IS '优惠券模板表'");
    }

    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS app_coupon_template');
    }
}
