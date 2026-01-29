<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateAppCheckinStatTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('app_checkin_stat', function (Blueprint $table) {
            $table->bigIncrements('stat_id')->comment('统计ID');
            $table->bigInteger('member_id')->unique()->comment('用户ID');
            $table->integer('total_checkin_days')->default(0)->comment('累计签到天数');
            $table->integer('continuous_days')->default(0)->comment('当前连续签到天数');
            $table->integer('max_continuous_days')->default(0)->comment('历史最大连续签到天数');
            $table->bigInteger('total_reward_value')->default(0)->comment('累计获得奖励');
            $table->date('last_checkin_date')->nullable()->comment('最后签到日期');
            $table->timestamp('last_checkin_time')->nullable()->comment('最后签到时间');
            $table->timestamp('create_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'))->comment('创建时间');
            $table->timestamp('update_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'))->comment('更新时间');

            $table->index('last_checkin_date', 'idx_checkin_stat_last_date');
        });

        DB::statement("COMMENT ON TABLE app_checkin_stat IS '用户签到统计表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('app_checkin_stat');
    }
}
