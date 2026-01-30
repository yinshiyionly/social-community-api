<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateAppMemberGrowthTaskTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('app_member_growth_task', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('ID');
            $table->bigInteger('member_id')->comment('用户ID');
            $table->integer('task_id')->comment('任务ID');
            $table->string('task_code', 50)->comment('任务编码');
            $table->smallInteger('is_completed')->default(0)->comment('是否完成：0未完成 1已完成');
            $table->timestamp('complete_time')->nullable()->comment('完成时间');
            $table->integer('point_value')->default(0)->comment('获得积分');
            $table->timestamp('create_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('update_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));

            $table->unique(['member_id', 'task_code'], 'uk_member_growth_task');
            $table->index('member_id', 'idx_growth_task_member_id');
        });

        DB::statement("COMMENT ON TABLE app_member_growth_task IS '用户成长任务状态表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('app_member_growth_task');
    }
}
