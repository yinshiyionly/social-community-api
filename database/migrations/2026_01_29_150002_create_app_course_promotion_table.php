<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAppCoursePromotionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("
            CREATE TABLE app_course_promotion (
                promotion_id int8 NOT NULL GENERATED ALWAYS AS IDENTITY (INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 START 1 CACHE 1),
                course_id int8 NOT NULL,
                landing_page_html text NULL,
                landing_page_sections jsonb NOT NULL DEFAULT '[]',
                landing_page_bg varchar(500) NULL,
                landing_page_theme varchar(20) NOT NULL DEFAULT 'default',
                seckill_enabled int2 NOT NULL DEFAULT 0,
                seckill_price numeric(10,2) NOT NULL DEFAULT 0,
                seckill_start_time timestamp(0) NULL,
                seckill_end_time timestamp(0) NULL,
                seckill_stock int4 NOT NULL DEFAULT 0,
                seckill_sold int4 NOT NULL DEFAULT 0,
                countdown_enabled int2 NOT NULL DEFAULT 0,
                countdown_type int2 NOT NULL DEFAULT 1,
                countdown_end_time timestamp(0) NULL,
                countdown_hours int4 NOT NULL DEFAULT 0,
                countdown_text varchar(100) NULL,
                fake_data_enabled int2 NOT NULL DEFAULT 0,
                fake_enroll_base int4 NOT NULL DEFAULT 0,
                fake_enroll_increment int4 NOT NULL DEFAULT 0,
                fake_view_base int4 NOT NULL DEFAULT 0,
                fake_view_increment int4 NOT NULL DEFAULT 0,
                fake_recent_buyers jsonb NOT NULL DEFAULT '[]',
                discount_enabled int2 NOT NULL DEFAULT 0,
                discount_price numeric(10,2) NOT NULL DEFAULT 0,
                discount_start_time timestamp(0) NULL,
                discount_end_time timestamp(0) NULL,
                discount_label varchar(50) NULL,
                group_buy_enabled int2 NOT NULL DEFAULT 0,
                group_buy_price numeric(10,2) NOT NULL DEFAULT 0,
                group_buy_size int4 NOT NULL DEFAULT 2,
                group_buy_hours int4 NOT NULL DEFAULT 24,
                distribute_enabled int2 NOT NULL DEFAULT 0,
                distribute_ratio numeric(5,2) NOT NULL DEFAULT 0,
                distribute_amount numeric(10,2) NOT NULL DEFAULT 0,
                coupon_ids jsonb NOT NULL DEFAULT '[]',
                created_at timestamp(0) NULL,
                updated_at timestamp(0) NULL,
                deleted_at timestamp(0) NULL,
                PRIMARY KEY (promotion_id)
            )
        ");

        // 列注释
        DB::statement("COMMENT ON COLUMN app_course_promotion.promotion_id IS '推广配置ID'");
        DB::statement("COMMENT ON COLUMN app_course_promotion.course_id IS '课程ID'");
        DB::statement("COMMENT ON COLUMN app_course_promotion.landing_page_html IS '落地页HTML内容'");
        DB::statement("COMMENT ON COLUMN app_course_promotion.landing_page_sections IS '落地页模块配置'");
        DB::statement("COMMENT ON COLUMN app_course_promotion.landing_page_bg IS '落地页背景图'");
        DB::statement("COMMENT ON COLUMN app_course_promotion.landing_page_theme IS '落地页主题'");
        DB::statement("COMMENT ON COLUMN app_course_promotion.seckill_enabled IS '秒杀开关：0=关 1=开'");
        DB::statement("COMMENT ON COLUMN app_course_promotion.seckill_price IS '秒杀价格'");
        DB::statement("COMMENT ON COLUMN app_course_promotion.seckill_start_time IS '秒杀开始时间'");
        DB::statement("COMMENT ON COLUMN app_course_promotion.seckill_end_time IS '秒杀结束时间'");
        DB::statement("COMMENT ON COLUMN app_course_promotion.seckill_stock IS '秒杀库存'");
        DB::statement("COMMENT ON COLUMN app_course_promotion.seckill_sold IS '秒杀已售'");
        DB::statement("COMMENT ON COLUMN app_course_promotion.countdown_enabled IS '倒计时开关：0=关 1=开'");
        DB::statement("COMMENT ON COLUMN app_course_promotion.countdown_type IS '倒计时类型：1=固定结束时间 2=访问后N小时'");
        DB::statement("COMMENT ON COLUMN app_course_promotion.countdown_end_time IS '倒计时结束时间'");
        DB::statement("COMMENT ON COLUMN app_course_promotion.countdown_hours IS '访问后倒计时小时数'");
        DB::statement("COMMENT ON COLUMN app_course_promotion.countdown_text IS '倒计时文案'");
        DB::statement("COMMENT ON COLUMN app_course_promotion.fake_data_enabled IS '虚假数据开关：0=关 1=开'");
        DB::statement("COMMENT ON COLUMN app_course_promotion.fake_enroll_base IS '虚假报名基数'");
        DB::statement("COMMENT ON COLUMN app_course_promotion.fake_enroll_increment IS '每日随机增量上限'");
        DB::statement("COMMENT ON COLUMN app_course_promotion.fake_view_base IS '虚假浏览基数'");
        DB::statement("COMMENT ON COLUMN app_course_promotion.fake_view_increment IS '每日随机增量上限'");
        DB::statement("COMMENT ON COLUMN app_course_promotion.fake_recent_buyers IS '虚假最近购买者列表'");
        DB::statement("COMMENT ON COLUMN app_course_promotion.discount_enabled IS '限时优惠开关：0=关 1=开'");
        DB::statement("COMMENT ON COLUMN app_course_promotion.discount_price IS '优惠价格'");
        DB::statement("COMMENT ON COLUMN app_course_promotion.discount_start_time IS '优惠开始时间'");
        DB::statement("COMMENT ON COLUMN app_course_promotion.discount_end_time IS '优惠结束时间'");
        DB::statement("COMMENT ON COLUMN app_course_promotion.discount_label IS '优惠标签'");
        DB::statement("COMMENT ON COLUMN app_course_promotion.group_buy_enabled IS '拼团开关：0=关 1=开'");
        DB::statement("COMMENT ON COLUMN app_course_promotion.group_buy_price IS '拼团价格'");
        DB::statement("COMMENT ON COLUMN app_course_promotion.group_buy_size IS '成团人数'");
        DB::statement("COMMENT ON COLUMN app_course_promotion.group_buy_hours IS '拼团有效小时'");
        DB::statement("COMMENT ON COLUMN app_course_promotion.distribute_enabled IS '分销开关：0=关 1=开'");
        DB::statement("COMMENT ON COLUMN app_course_promotion.distribute_ratio IS '分销比例%'");
        DB::statement("COMMENT ON COLUMN app_course_promotion.distribute_amount IS '固定分销金额'");
        DB::statement("COMMENT ON COLUMN app_course_promotion.coupon_ids IS '可用优惠券ID列表'");

        // 索引
        DB::statement('CREATE UNIQUE INDEX uk_app_course_promotion_course_id ON app_course_promotion (course_id)');

        // 表注释
        DB::statement("COMMENT ON TABLE app_course_promotion IS '课程推广配置表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS app_course_promotion');
    }
}
