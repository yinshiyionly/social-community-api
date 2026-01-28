<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateAppPostBaseTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('app_post_base', function (Blueprint $table) {
            $table->bigIncrements('post_id');
            $table->bigInteger('member_id')->index('idx_post_member');
            $table->smallInteger('post_type')->default(1)->comment('动态类型：1=图文 2=视频 3=文章');
            $table->string('title', 200)->default('');
            $table->text('content')->default('')->comment('内容/文章摘要');
            $table->text('content_html')->default('')->comment('文章HTML内容（post_type=3时使用）');
            $table->jsonb('media_data')->default('[]');
            $table->jsonb('cover')->default('{}')->comment('封面图信息：{url, width, height}');
            $table->smallInteger('image_style')->default(1)->comment('图片样式：1=大图 2=拼图');
            $table->string('location_name', 100)->default('');
            $table->jsonb('location_geo')->default('{}');
            $table->integer('view_count')->default(0);
            $table->integer('like_count')->default(0);
            $table->integer('comment_count')->default(0);
            $table->integer('share_count')->default(0);
            $table->integer('collection_count')->default(0);
            $table->smallInteger('is_top')->default(0);
            $table->smallInteger('visible')->default(1)->comment('可见性：0=私密 1=公开');
            $table->smallInteger('status')->default(1)->comment('状态：0=待审核 1=已通过 2=已拒绝');
            $table->string('audit_msg', 255)->default('');
            $table->decimal('sort_score', 16, 6)->default(0)->comment('排序分（物化计算）');
            $table->timestamps();
            $table->softDeletes();

            $table->index('post_type', 'idx_post_type');
            $table->index('created_at', 'idx_post_create');
        });

        // 复合索引用于列表排序
        DB::statement('CREATE INDEX idx_post_list_sort ON app_post_base (status, visible, is_top, sort_score, post_id)');

        DB::statement("COMMENT ON TABLE app_post_base IS '动态基础表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('app_post_base');
    }
}
