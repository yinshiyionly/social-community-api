<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateAppMemberOauthTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('app_member_oauth', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('主键ID');
            $table->bigInteger('member_id')->default(0)->comment('会员ID');
            $table->string('platform', 20)->comment('平台标识: wechat_mp(小程序), wechat_app(APP), wechat_h5(公众号), qq, apple');
            $table->string('openid', 128)->comment('第三方平台用户唯一标识');
            $table->string('unionid', 128)->default('')->comment('微信开放平台unionid，用于打通多端');
            $table->string('nickname', 100)->default('')->comment('第三方平台昵称');
            $table->string('avatar', 500)->default('')->comment('第三方平台头像');
            $table->smallInteger('gender')->default(0)->comment('性别: 0未知 1男 2女');
            $table->string('country', 50)->default('')->comment('国家');
            $table->string('province', 50)->default('')->comment('省份');
            $table->string('city', 50)->default('')->comment('城市');
            $table->jsonb('raw_data')->nullable()->comment('第三方返回的原始数据');
            $table->string('access_token', 512)->default('')->comment('访问令牌');
            $table->string('refresh_token', 512)->default('')->comment('刷新令牌');
            $table->timestamp('token_expires_at')->nullable()->comment('令牌过期时间');
            $table->timestamps();

            // 索引
            $table->unique(['platform', 'openid'], 'uk_platform_openid');
            $table->index('member_id', 'idx_oauth_member_id');
            $table->index('unionid', 'idx_oauth_unionid');
        });

        // 添加表注释
        DB::statement("COMMENT ON TABLE app_member_oauth IS '会员第三方账号关联表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('app_member_oauth');
    }
}
