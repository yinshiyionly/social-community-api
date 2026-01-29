<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateAppCourseGroupBuyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 拼团活动表
        Schema::create('app_course_group', function (Blueprint $table) {
            $table->bigIncrements('group_id')->comment('拼团ID');
            $table->bigInteger('course_id')->comment('课程ID');
            $table->bigInteger('leader_id')->comment('团长用户ID');
            $table->string('order_no', 64)->comment('团长订单号');
            $table->integer('group_size')->default(2)->comment('成团人数');
            $table->integer('current_size')->default(1)->comment('当前人数');
            $table->decimal('group_price', 10, 2)->comment('拼团价格');
            $table->smallInteger('status')->default(0)->comment('状态：0拼团中 1已成团 2已失败 3已取消');
            $table->timestamp('expire_time')->nullable()->comment('过期时间');
            $table->timestamp('success_time')->nullable()->comment('成团时间');
            $table->timestamp('create_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('update_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));

            $table->index('course_id', 'idx_group_course');
            $table->index('leader_id', 'idx_group_leader');
            $table->index('status', 'idx_group_status');
            $table->index(['course_id', 'status'], 'idx_group_course_status');
        });
        DB::statement("COMMENT ON TABLE app_course_group IS '课程拼团表'");

        // 拼团成员表
        Schema::create('app_course_group_member', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('ID');
            $table->bigInteger('group_id')->comment('拼团ID');
            $table->bigInteger('member_id')->comment('用户ID');
            $table->string('order_no', 64)->comment('订单号');
            $table->smallInteger('is_leader')->default(0)->comment('是否团长');
            $table->smallInteger('status')->default(0)->comment('状态：0待支付 1已支付 2已退款');
            $table->timestamp('join_time')->nullable()->comment('加入时间');
            $table->timestamp('create_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));

            $table->unique(['group_id', 'member_id'], 'uk_group_member');
            $table->index('member_id', 'idx_gm_member');
            $table->index('order_no', 'idx_gm_order');
        });
        DB::statement("COMMENT ON TABLE app_course_group_member IS '拼团成员表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('app_course_group_member');
        Schema::dropIfExists('app_course_group');
    }
}
