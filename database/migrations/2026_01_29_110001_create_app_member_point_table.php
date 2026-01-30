<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateAppMemberPointTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('app_member_point', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('ID');
            $table->bigInteger('member_id')->unique()->comment('用户ID');
            $table->bigInteger('total_points')->default(0)->comment('累计获得积分');
            $table->bigInteger('used_points')->default(0)->comment('已使用积分');
            $table->bigInteger('available_points')->default(0)->comment('可用积分');
            $table->bigInteger('frozen_points')->default(0)->comment('冻结积分');
            $table->bigInteger('expired_points')->default(0)->comment('已过期积分');
            $table->bigInteger('level_points')->default(0)->comment('等级积分');
            $table->timestamp('create_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('update_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));

            $table->index('available_points', 'idx_member_point_available');
        });

        DB::statement("COMMENT ON TABLE app_member_point IS '用户积分账户表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('app_member_point');
    }
}
