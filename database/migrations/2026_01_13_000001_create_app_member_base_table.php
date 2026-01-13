<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateAppMemberBaseTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('app_member_base', function (Blueprint $table) {
            $table->bigIncrements('member_id')->comment('会员ID');
            $table->string('phone', 20)->default('')->comment('手机号');
            $table->string('email', 100)->default('')->comment('邮箱');
            $table->string('password', 100)->default('')->comment('密码');
            $table->string('nickname', 50)->default('')->comment('昵称');
            $table->string('avatar', 255)->default('')->comment('头像URL');
            $table->smallInteger('gender')->default(0)->comment('性别: 0未知 1男 2女');
            $table->date('birthday')->nullable()->comment('生日');
            $table->string('bio', 500)->default('')->comment('个人简介');
            $table->integer('level')->default(1)->comment('会员等级');
            $table->integer('points')->default(0)->comment('积分');
            $table->integer('coin')->default(0)->comment('金币');
            $table->string('invite_code', 20)->default('')->comment('邀请码');
            $table->bigInteger('inviter_id')->default(0)->comment('邀请人ID');
            $table->smallInteger('status')->default(1)->comment('状态: 1正常 2禁用');
            $table->timestamps();
            $table->softDeletes();

            // 索引
            $table->index('phone', 'idx_member_phone');
            $table->index('invite_code', 'idx_member_invite_code');
            $table->index('inviter_id', 'idx_member_inviter_id');
            $table->index('status', 'idx_member_status');
        });

        // 设置自增起始值为 3545623190
        DB::statement('ALTER SEQUENCE app_member_base_member_id_seq RESTART WITH 3545623190');

        // 添加表注释
        DB::statement("COMMENT ON TABLE app_member_base IS '会员基础信息表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('app_member_base');
    }
}
