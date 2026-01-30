<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateAppCourseOrderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 课程订单表
        Schema::create('app_course_order', function (Blueprint $table) {
            $table->bigIncrements('order_id')->comment('订单ID');
            $table->string('order_no', 64)->unique()->comment('订单号');
            $table->bigInteger('member_id')->comment('用户ID');
            $table->bigInteger('course_id')->comment('课程ID');
            $table->string('course_title', 200)->comment('课程标题（快照）');
            $table->string('course_cover', 500)->nullable()->comment('课程封面（快照）');
            
            // 价格信息
            $table->decimal('original_price', 10, 2)->default(0)->comment('原价');
            $table->decimal('current_price', 10, 2)->default(0)->comment('现价');
            $table->decimal('discount_amount', 10, 2)->default(0)->comment('优惠金额');
            $table->decimal('coupon_amount', 10, 2)->default(0)->comment('优惠券抵扣');
            $table->integer('point_deduct')->default(0)->comment('积分抵扣数');
            $table->decimal('point_amount', 10, 2)->default(0)->comment('积分抵扣金额');
            $table->decimal('paid_amount', 10, 2)->default(0)->comment('实付金额');
            
            // 优惠信息
            $table->string('coupon_id', 64)->nullable()->comment('优惠券ID');
            $table->string('promotion_type', 50)->nullable()->comment('促销类型：seckill/discount/group');
            $table->string('promotion_id', 64)->nullable()->comment('促销活动ID');
            
            // 支付信息
            $table->smallInteger('pay_status')->default(0)->comment('支付状态：0待支付 1已支付 2已退款 3已关闭');
            $table->smallInteger('pay_type')->nullable()->comment('支付方式：1微信 2支付宝 3余额 4免费');
            $table->string('pay_trade_no', 100)->nullable()->comment('支付流水号');
            $table->timestamp('pay_time')->nullable()->comment('支付时间');
            $table->timestamp('expire_time')->nullable()->comment('订单过期时间');
            
            // 退款信息
            $table->smallInteger('refund_status')->default(0)->comment('退款状态：0无 1申请中 2已退款 3已拒绝');
            $table->decimal('refund_amount', 10, 2)->default(0)->comment('退款金额');
            $table->string('refund_reason', 500)->nullable()->comment('退款原因');
            $table->timestamp('refund_time')->nullable()->comment('退款时间');
            
            // 分销信息
            $table->bigInteger('inviter_id')->nullable()->comment('邀请人ID');
            $table->decimal('commission_amount', 10, 2)->default(0)->comment('佣金金额');
            $table->smallInteger('commission_status')->default(0)->comment('佣金状态：0待结算 1已结算');
            
            // 其他
            $table->string('remark', 500)->nullable()->comment('备注');
            $table->string('client_ip', 50)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('create_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('update_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));

            $table->index('member_id', 'idx_order_member');
            $table->index('course_id', 'idx_order_course');
            $table->index('pay_status', 'idx_order_pay_status');
            $table->index('create_time', 'idx_order_create_time');
            $table->index(['member_id', 'pay_status'], 'idx_order_member_status');
        });
        DB::statement("COMMENT ON TABLE app_course_order IS '课程订单表'");

        // 订单支付日志
        Schema::create('app_course_order_pay_log', function (Blueprint $table) {
            $table->bigIncrements('log_id')->comment('日志ID');
            $table->string('order_no', 64)->comment('订单号');
            $table->bigInteger('member_id')->comment('用户ID');
            $table->smallInteger('pay_type')->comment('支付方式');
            $table->decimal('pay_amount', 10, 2)->comment('支付金额');
            $table->string('trade_no', 100)->nullable()->comment('第三方流水号');
            $table->smallInteger('pay_result')->comment('支付结果：0失败 1成功');
            $table->text('pay_response')->nullable()->comment('支付响应');
            $table->string('client_ip', 50)->nullable();
            $table->timestamp('create_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));

            $table->index('order_no', 'idx_pay_log_order');
            $table->index('member_id', 'idx_pay_log_member');
        });
        DB::statement("COMMENT ON TABLE app_course_order_pay_log IS '订单支付日志表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('app_course_order_pay_log');
        Schema::dropIfExists('app_course_order');
    }
}
