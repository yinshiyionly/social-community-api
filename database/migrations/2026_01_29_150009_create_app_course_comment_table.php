<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateAppCourseCommentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 课程评价表
        Schema::create('app_course_comment', function (Blueprint $table) {
            $table->bigIncrements('comment_id')->comment('评价ID');
            $table->bigInteger('course_id')->comment('课程ID');
            $table->bigInteger('member_id')->comment('用户ID');
            $table->bigInteger('order_id')->nullable()->comment('订单ID');
            $table->smallInteger('rating')->default(5)->comment('评分：1-5');
            $table->text('content')->nullable()->comment('评价内容');
            $table->jsonb('images')->default('[]')->comment('评价图片');
            $table->smallInteger('is_anonymous')->default(0)->comment('是否匿名');
            $table->integer('like_count')->default(0)->comment('点赞数');
            $table->smallInteger('is_top')->default(0)->comment('是否置顶');
            $table->smallInteger('is_excellent')->default(0)->comment('是否精选');
            $table->smallInteger('status')->default(1)->comment('状态：0待审核 1已通过 2已拒绝');
            
            // 商家回复
            $table->text('reply_content')->nullable()->comment('商家回复');
            $table->timestamp('reply_time')->nullable()->comment('回复时间');
            $table->bigInteger('reply_by')->nullable()->comment('回复人');
            
            $table->string('client_ip', 50)->nullable();
            $table->timestamp('create_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('update_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->smallInteger('del_flag')->default(0)->comment('删除标志');

            $table->index('course_id', 'idx_comment_course');
            $table->index('member_id', 'idx_comment_member');
            $table->index(['course_id', 'status', 'is_top'], 'idx_comment_list');
        });
        DB::statement("COMMENT ON TABLE app_course_comment IS '课程评价表'");

        // 课程收藏表
        Schema::create('app_course_favorite', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('ID');
            $table->bigInteger('member_id')->comment('用户ID');
            $table->bigInteger('course_id')->comment('课程ID');
            $table->timestamp('create_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));

            $table->unique(['member_id', 'course_id'], 'uk_member_course_fav');
            $table->index('member_id', 'idx_fav_member');
            $table->index('course_id', 'idx_fav_course');
        });
        DB::statement("COMMENT ON TABLE app_course_favorite IS '课程收藏表'");

        // 课程浏览记录表
        Schema::create('app_course_view_log', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('ID');
            $table->bigInteger('member_id')->nullable()->comment('用户ID');
            $table->bigInteger('course_id')->comment('课程ID');
            $table->string('device_id', 100)->nullable()->comment('设备ID');
            $table->string('client_ip', 50)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->string('referer', 500)->nullable()->comment('来源页');
            $table->integer('duration')->default(0)->comment('停留时长（秒）');
            $table->timestamp('create_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));

            $table->index('member_id', 'idx_view_member');
            $table->index('course_id', 'idx_view_course');
            $table->index('create_time', 'idx_view_time');
        });
        DB::statement("COMMENT ON TABLE app_course_view_log IS '课程浏览记录表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('app_course_view_log');
        Schema::dropIfExists('app_course_favorite');
        Schema::dropIfExists('app_course_comment');
    }
}
