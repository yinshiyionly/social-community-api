<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateAppPointLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('app_point_log', function (Blueprint $table) {
            $table->bigIncrements('log_id')->comment('日志ID');
            $table->bigInteger('member_id')->comment('用户ID');
            $table->smallInteger('change_type')->comment('变动类型：1获取 2消费 3冻结 4解冻 5过期 6后台调整');
            $table->integer('change_value')->comment('变动积分值');
            $table->bigInteger('before_points')->comment('变动前可用积分');
            $table->bigInteger('after_points')->comment('变动后可用积分');
            $table->smallInteger('source_type')->comment('来源类型：1任务奖励 2消费抵扣 3订单退款 4后台赠送 5后台扣除 6过期清零 7活动奖励');
            $table->string('source_id', 64)->nullable()->comment('来源ID');
            $table->string('task_code', 50)->nullable()->comment('关联任务编码');
            $table->string('order_no', 64)->nullable()->comment('关联订单号');
            $table->string('title', 200)->comment('流水标题');
            $table->string('remark', 500)->nullable()->comment('备注说明');
            $table->bigInteger('operator_id')->nullable()->comment('操作人ID');
            $table->string('operator_name', 64)->nullable()->comment('操作人名称');
            $table->timestamp('expire_time')->nullable()->comment('积分过期时间');
            $table->string('client_ip', 50)->nullable();
            $table->timestamp('create_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));

            $table->index('member_id', 'idx_point_log_member_id');
            $table->index('change_type', 'idx_point_log_change_type');
            $table->index('source_type', 'idx_point_log_source_type');
            $table->index('task_code', 'idx_point_log_task_code');
            $table->index('create_time', 'idx_point_log_create_time');
            $table->index(['member_id', 'create_time'], 'idx_point_log_member_time');
        });

        DB::statement("COMMENT ON TABLE app_point_log IS '积分流水日志表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('app_point_log');
    }
}
