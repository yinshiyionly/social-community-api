<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateAppCheckinConfigTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('app_checkin_config', function (Blueprint $table) {
            $table->increments('config_id')->comment('配置ID');
            $table->smallInteger('day_num')->comment('连续签到天数');
            $table->smallInteger('reward_type')->default(1)->comment('奖励类型：1积分 2经验值');
            $table->integer('reward_value')->default(0)->comment('奖励数值');
            $table->integer('extra_reward_value')->default(0)->comment('额外奖励数值');
            $table->smallInteger('status')->default(1)->comment('状态：1启用 2禁用');
            $table->string('remark', 200)->nullable()->comment('备注说明');
            $table->string('create_by', 64)->nullable()->comment('创建者');
            $table->timestamp('create_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'))->comment('创建时间');
            $table->string('update_by', 64)->nullable()->comment('更新者');
            $table->timestamp('update_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'))->comment('更新时间');
            $table->smallInteger('del_flag')->default(0)->comment('删除标志：0正常 1删除');

            $table->unique(['day_num', 'reward_type', 'del_flag'], 'uk_day_num_reward_type');
        });

        DB::statement("COMMENT ON TABLE app_checkin_config IS '签到奖励配置表'");

        // 初始化7天签到奖励配置
        DB::table('app_checkin_config')->insert([
            ['day_num' => 1, 'reward_type' => 1, 'reward_value' => 10, 'extra_reward_value' => 0, 'remark' => '第1天签到奖励'],
            ['day_num' => 2, 'reward_type' => 1, 'reward_value' => 15, 'extra_reward_value' => 0, 'remark' => '第2天签到奖励'],
            ['day_num' => 3, 'reward_type' => 1, 'reward_value' => 20, 'extra_reward_value' => 0, 'remark' => '第3天签到奖励'],
            ['day_num' => 4, 'reward_type' => 1, 'reward_value' => 25, 'extra_reward_value' => 0, 'remark' => '第4天签到奖励'],
            ['day_num' => 5, 'reward_type' => 1, 'reward_value' => 30, 'extra_reward_value' => 0, 'remark' => '第5天签到奖励'],
            ['day_num' => 6, 'reward_type' => 1, 'reward_value' => 40, 'extra_reward_value' => 0, 'remark' => '第6天签到奖励'],
            ['day_num' => 7, 'reward_type' => 1, 'reward_value' => 50, 'extra_reward_value' => 50, 'remark' => '第7天签到奖励，额外奖励50'],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('app_checkin_config');
    }
}
