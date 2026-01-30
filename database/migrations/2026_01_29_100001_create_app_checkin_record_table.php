<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateAppCheckinRecordTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('app_checkin_record', function (Blueprint $table) {
            $table->bigIncrements('record_id')->comment('记录ID');
            $table->bigInteger('member_id')->comment('用户ID');
            $table->date('checkin_date')->comment('签到日期');
            $table->integer('continuous_days')->default(1)->comment('当次连续签到天数');
            $table->smallInteger('reward_type')->default(1)->comment('获得奖励类型');
            $table->integer('reward_value')->default(0)->comment('获得奖励数值');
            $table->integer('extra_reward_value')->default(0)->comment('获得额外奖励');
            $table->timestamp('checkin_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'))->comment('签到时间');
            $table->string('client_ip', 50)->nullable()->comment('签到IP');
            $table->string('device_info', 200)->nullable()->comment('设备信息');
            $table->timestamp('create_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'))->comment('创建时间');

            $table->unique(['member_id', 'checkin_date'], 'uk_member_checkin_date');
            $table->index('member_id', 'idx_checkin_record_member_id');
            $table->index('checkin_date', 'idx_checkin_record_checkin_date');
        });

        DB::statement("COMMENT ON TABLE app_checkin_record IS '用户签到记录表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('app_checkin_record');
    }
}
