<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateAppMemberTaskRecordTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('app_member_task_record', function (Blueprint $table) {
            $table->bigIncrements('record_id')->comment('记录ID');
            $table->bigInteger('member_id')->comment('用户ID');
            $table->integer('task_id')->comment('任务ID');
            $table->string('task_code', 50)->comment('任务编码');
            $table->smallInteger('task_type')->comment('任务类型');
            $table->integer('point_value')->comment('获得积分');
            $table->date('complete_date')->comment('完成日期');
            $table->integer('complete_count')->default(1)->comment('当日完成次数');
            $table->string('biz_id', 64)->nullable()->comment('业务ID');
            $table->timestamp('create_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));

            $table->unique(['member_id', 'task_code', 'complete_date', 'biz_id'], 'uk_member_task_date_biz');
            $table->index('member_id', 'idx_task_record_member_id');
            $table->index('task_code', 'idx_task_record_task_code');
            $table->index('complete_date', 'idx_task_record_complete_date');
            $table->index(['member_id', 'task_code', 'complete_date'], 'idx_task_record_member_task_date');
        });

        DB::statement("COMMENT ON TABLE app_member_task_record IS '用户任务完成记录表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('app_member_task_record');
    }
}
