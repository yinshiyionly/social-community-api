<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
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
        Schema::create('app_course_promotion', function (Blueprint $table) {
            $table->bigIncrements('promotion_id')->comment('推广配置ID');
            $table->bigInteger('course_id')->unique()->comment('课程ID');
            
            // 落地页配置
            $table->text('landing_page_html')->nullable()->comment('落地页HTML内容');
            $table->jsonb('landing_page_sections')->default('[]')->comment('落地页模块配置');
            $table->string('landing_page_bg', 500)->nullable()->comment('落地页背景图');
            $table->string('landing_page_theme', 20)->default('default')->comment('落地页主题');
            
            // 秒杀配置
            $table->smallInteger('seckill_enabled')->default(0)->comment('秒杀开关：0关 1开');
            $table->decimal('seckill_price', 10, 2)->default(0)->comment('秒杀价格');
            $table->timestamp('seckill_start_time')->nullable()->comment('秒杀开始时间');
            $table->timestamp('seckill_end_time')->nullable()->comment('秒杀结束时间');
            $table->integer('seckill_stock')->default(0)->comment('秒杀库存');
            $table->integer('seckill_sold')->default(0)->comment('秒杀已售');
            
            // 倒计时配置
            $table->smallInteger('countdown_enabled')->default(0)->comment('倒计时开关：0关 1开');
            $table->smallInteger('countdown_type')->default(1)->comment('倒计时类型：1固定结束时间 2访问后N小时');
            $table->timestamp('countdown_end_time')->nullable()->comment('倒计时结束时间');
            $table->integer('countdown_hours')->default(0)->comment('访问后倒计时小时数');
            $table->string('countdown_text', 100)->nullable()->comment('倒计时文案');
            
            // 虚假数据配置（营销展示）
            $table->smallInteger('fake_data_enabled')->default(0)->comment('虚假数据开关：0关 1开');
            $table->integer('fake_enroll_base')->default(0)->comment('虚假报名基数');
            $table->integer('fake_enroll_increment')->default(0)->comment('每日随机增量上限');
            $table->integer('fake_view_base')->default(0)->comment('虚假浏览基数');
            $table->integer('fake_view_increment')->default(0)->comment('每日随机增量上限');
            $table->jsonb('fake_recent_buyers')->default('[]')->comment('虚假最近购买者列表');
            
            // 限时优惠配置
            $table->smallInteger('discount_enabled')->default(0)->comment('限时优惠开关：0关 1开');
            $table->decimal('discount_price', 10, 2)->default(0)->comment('优惠价格');
            $table->timestamp('discount_start_time')->nullable()->comment('优惠开始时间');
            $table->timestamp('discount_end_time')->nullable()->comment('优惠结束时间');
            $table->string('discount_label', 50)->nullable()->comment('优惠标签');
            
            // 拼团配置
            $table->smallInteger('group_buy_enabled')->default(0)->comment('拼团开关：0关 1开');
            $table->decimal('group_buy_price', 10, 2)->default(0)->comment('拼团价格');
            $table->integer('group_buy_size')->default(2)->comment('成团人数');
            $table->integer('group_buy_hours')->default(24)->comment('拼团有效小时');
            
            // 分销配置
            $table->smallInteger('distribute_enabled')->default(0)->comment('分销开关：0关 1开');
            $table->decimal('distribute_ratio', 5, 2)->default(0)->comment('分销比例%');
            $table->decimal('distribute_amount', 10, 2)->default(0)->comment('固定分销金额');
            
            // 优惠券配置
            $table->jsonb('coupon_ids')->default('[]')->comment('可用优惠券ID列表');
            
            // 系统字段
            $table->timestamp('create_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('update_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
        });

        DB::statement("COMMENT ON TABLE app_course_promotion IS '课程推广配置表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('app_course_promotion');
    }
}
