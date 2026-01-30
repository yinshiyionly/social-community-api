<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateAppCourseChapterContentTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 图文课章节内容表
        Schema::create('app_chapter_content_article', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('chapter_id')->unique()->comment('章节ID');
            $table->text('content_html')->nullable()->comment('图文内容HTML');
            $table->jsonb('images')->default('[]')->comment('图片列表');
            $table->jsonb('attachments')->default('[]')->comment('附件列表');
            $table->integer('word_count')->default(0)->comment('字数');
            $table->integer('read_time')->default(0)->comment('预计阅读时间（秒）');
            $table->timestamp('create_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('update_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
        });
        DB::statement("COMMENT ON TABLE app_chapter_content_article IS '图文课章节内容表'");

        // 录播课章节内容表
        Schema::create('app_chapter_content_video', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('chapter_id')->unique()->comment('章节ID');
            $table->string('video_url', 500)->nullable()->comment('视频地址');
            $table->string('video_id', 100)->nullable()->comment('视频ID（云存储）');
            $table->string('video_source', 50)->default('local')->comment('视频来源：local/aliyun/tencent/volcengine');
            $table->integer('duration')->default(0)->comment('视频时长（秒）');
            $table->integer('width')->default(0)->comment('视频宽度');
            $table->integer('height')->default(0)->comment('视频高度');
            $table->bigInteger('file_size')->default(0)->comment('文件大小（字节）');
            $table->string('cover_image', 500)->nullable()->comment('视频封面');
            $table->jsonb('quality_list')->default('[]')->comment('清晰度列表');
            $table->jsonb('subtitles')->default('[]')->comment('字幕列表');
            $table->jsonb('attachments')->default('[]')->comment('课件附件');
            $table->smallInteger('allow_download')->default(0)->comment('允许下载');
            $table->smallInteger('drm_enabled')->default(0)->comment('DRM加密');
            $table->timestamp('create_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('update_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
        });
        DB::statement("COMMENT ON TABLE app_chapter_content_video IS '录播课章节内容表'");

        // 直播课章节内容表
        Schema::create('app_chapter_content_live', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('chapter_id')->unique()->comment('章节ID');
            $table->string('live_platform', 50)->default('custom')->comment('直播平台：custom/aliyun/tencent/agora');
            $table->string('live_room_id', 100)->nullable()->comment('直播间ID');
            $table->string('live_push_url', 500)->nullable()->comment('推流地址');
            $table->string('live_pull_url', 500)->nullable()->comment('拉流地址');
            $table->string('live_cover', 500)->nullable()->comment('直播封面');
            $table->timestamp('live_start_time')->nullable()->comment('直播开始时间');
            $table->timestamp('live_end_time')->nullable()->comment('直播结束时间');
            $table->integer('live_duration')->default(0)->comment('预计时长（分钟）');
            $table->smallInteger('live_status')->default(0)->comment('直播状态：0未开始 1直播中 2已结束 3已取消');
            $table->smallInteger('has_playback')->default(0)->comment('是否有回放');
            $table->string('playback_url', 500)->nullable()->comment('回放地址');
            $table->integer('playback_duration')->default(0)->comment('回放时长');
            $table->smallInteger('allow_chat')->default(1)->comment('允许聊天');
            $table->smallInteger('allow_gift')->default(0)->comment('允许送礼');
            $table->integer('online_count')->default(0)->comment('在线人数');
            $table->integer('max_online_count')->default(0)->comment('最高在线');
            $table->jsonb('attachments')->default('[]')->comment('直播资料');
            $table->timestamp('create_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('update_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));

            $table->index('live_start_time', 'idx_live_start_time');
            $table->index('live_status', 'idx_live_status');
        });
        DB::statement("COMMENT ON TABLE app_chapter_content_live IS '直播课章节内容表'");

        // 音频课章节内容表
        Schema::create('app_chapter_content_audio', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('chapter_id')->unique()->comment('章节ID');
            $table->string('audio_url', 500)->nullable()->comment('音频地址');
            $table->string('audio_id', 100)->nullable()->comment('音频ID（云存储）');
            $table->string('audio_source', 50)->default('local')->comment('音频来源');
            $table->integer('duration')->default(0)->comment('音频时长（秒）');
            $table->bigInteger('file_size')->default(0)->comment('文件大小');
            $table->string('cover_image', 500)->nullable()->comment('音频封面');
            $table->text('transcript')->nullable()->comment('文字稿');
            $table->jsonb('timeline_text')->default('[]')->comment('时间轴文字');
            $table->jsonb('attachments')->default('[]')->comment('附件资料');
            $table->smallInteger('allow_download')->default(0)->comment('允许下载');
            $table->smallInteger('background_play')->default(1)->comment('允许后台播放');
            $table->timestamp('create_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('update_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
        });
        DB::statement("COMMENT ON TABLE app_chapter_content_audio IS '音频课章节内容表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('app_chapter_content_audio');
        Schema::dropIfExists('app_chapter_content_live');
        Schema::dropIfExists('app_chapter_content_video');
        Schema::dropIfExists('app_chapter_content_article');
    }
}
