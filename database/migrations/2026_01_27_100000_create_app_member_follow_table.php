<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateAppMemberFollowTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('app_member_follow', function (Blueprint $table) {
            $table->bigIncrements('follow_id')->comment('主键ID');
            $table->bigInteger('member_id')->comment('关注者ID');
            $table->bigInteger('follow_member_id')->comment('被关注者ID');
            $table->string('source', 32)->nullable()->comment('关注来源：search/recommend/profile/qr/post/comment');
            $table->smallInteger('status')->default(1)->comment('状态：1-正常 2-取消关注');
            $table->timestamps();

            // 唯一索引
            $table->unique(['member_id', 'follow_member_id'], 'uk_member_follow');
            // 普通索引
            $table->index(['member_id', 'status'], 'idx_follow_member');
            $table->index(['follow_member_id', 'status'], 'idx_follow_target');
            $table->index('created_at', 'idx_follow_created');
        });

        // 添加表注释
        DB::statement("COMMENT ON TABLE app_member_follow IS '用户关注关系表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('app_member_follow');
    }
}
