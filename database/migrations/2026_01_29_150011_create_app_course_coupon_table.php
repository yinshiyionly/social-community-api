<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateAppCourseCouponTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 优惠券模板表
        Schema::create('app_coupon_template', function (Blueprint $table) {
            $table->bigIncrements('coupon_id')->comment('优惠券ID');
            $table->string('coupon_name', 100)->comment('优惠券名称');
            $table->smallInteger('coupon_type')->default(1)->comment('类型：1满减券 2折扣券 3无门槛券');
            $table->decimal('threshold_amount', 10, 2)->default(0)->comment('使用门槛金额');
            $table->decimal('discount_amount', 10, 2)->default(0)->comment('优惠金额');
            $table->decimal('discount_rate', 5, 2)->default(0)->comment('折扣率（0.01-1）');
            $table->decimal('max_discount', 10, 2)->default(0)->comment('最大优惠金额');
            
            // 适用范围
            $table->smallInteger('scope_type')->default(1)->comment('适用范围：1全部课程 2指定分类 3指定课程');
            $table->jsonb('scope_ids')->default('[]')->comment('适用ID列表');
            
            // 发放配置
            $table->integer('total_count')->default(0)->comment('发放总量（0=不限）');
            $table->integer('issued_count')->default(0)->comment('已发放数量');
            $table->integer('used_count')->default(0)->comment('已使用数量');
            $table->integer('per_limit')->default(1)->comment('每人限领数量');
            
            // 有效期配置
            $table->smallInteger('valid_type')->default(1)->comment('有效期类型：1固定时间 2领取后N天');
            $table->timestamp('valid_start_time')->nullable()->comment('有效开始时间');
            $table->timestamp('valid_end_time')->nullable()->comment('有效结束时间');
            $table->integer('valid_days')->default(0)->comment('领取后有效天数');
            
            // 领取配置
            $table->smallInteger('receive_type')->default(1)->comment('领取方式：1公开领取 2系统发放 3兑换码');
            $table->timestamp('receive_start_time')->nullable()->comment('领取开始时间');
            $table->timestamp('receive_end_time')->nullable()->comment('领取结束时间');
            
            $table->integer('sort_order')->default(0)->comment('排序');
            $table->smallInteger('status')->default(1)->comment('状态：1启用 2禁用');
            $table->string('create_by', 64)->nullable();
            $table->timestamp('create_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->string('update_by', 64)->nullable();
            $table->timestamp('update_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->smallInteger('del_flag')->default(0)->comment('删除标志');

            $table->index('status', 'idx_coupon_status');
            $table->index('coupon_type', 'idx_coupon_type');
        });
        DB::statement("COMMENT ON TABLE app_coupon_template IS '优惠券模板表'");

        // 用户优惠券表
        Schema::create('app_member_coupon', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('ID');
            $table->bigInteger('coupon_id')->comment('优惠券模板ID');
            $table->bigInteger('member_id')->comment('用户ID');
            $table->string('coupon_code', 32)->nullable()->comment('优惠券码');
            $table->smallInteger('status')->default(0)->comment('状态：0未使用 1已使用 2已过期');
            $table->timestamp('receive_time')->nullable()->comment('领取时间');
            $table->timestamp('expire_time')->nullable()->comment('过期时间');
            $table->timestamp('use_time')->nullable()->comment('使用时间');
            $table->string('use_order_no', 64)->nullable()->comment('使用订单号');
            $table->timestamp('create_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('update_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));

            $table->index('member_id', 'idx_mc_member');
            $table->index('coupon_id', 'idx_mc_coupon');
            $table->index(['member_id', 'status'], 'idx_mc_member_status');
            $table->index('coupon_code', 'idx_mc_code');
        });
        DB::statement("COMMENT ON TABLE app_member_coupon IS '用户优惠券表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('app_member_coupon');
        Schema::dropIfExists('app_coupon_template');
    }
}
