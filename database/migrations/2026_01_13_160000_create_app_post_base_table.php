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
            $table->smallInteger('post_type')->default(1)->comment('动态类型：1=图文 2=视频 3=文章');
            $table->bigInteger('member_id')->comment('会员ID');
            $table->text('title')->default('')->comment('标题');
            $table->text('content')->default('')->comment('内容');
            $table->jsonb('media_data')->default('[]')->comment('媒体数据');
            $table->jsonb('cover')->default('{}')->comment('封面图信息：{url, width, height}');
            $table->smallInteger('image_show_style')->default(1)->comment('图文动态图片展示样式：1=大图 2=拼图');
            $table->smallInteger('article_cover_style')->default(1)->comment('文章封面样式：1=单图 2=双图 3=三图');
            $table->smallInteger('is_top')->default(0)->comment('是否置顶：0=否 1=是');
            $table->decimal('sort_score', 16, 6)->default(0)->comment('排序分物化计算');
            $table->smallInteger('visible')->default(1)->comment('可见性：0=私密 1=公开');
            $table->smallInteger('status')->default(0)->comment('状态：0=待审核 1=已通过 2=已拒绝');
            $table->timestamps();
            $table->softDeletes();

            // 索引
            $table->index('post_type', 'idx_app_post_base_post_type');
            $table->index('member_id', 'idx_app_post_base_member_id');
            $table->index('status', 'idx_app_post_base_status');
            $table->index('created_at', 'idx_app_post_base_created_at');
        });

        // 复合索引用于列表排序
        DB::statement('CREATE INDEX idx_app_post_base_list_sort ON app_post_base (status, visible, is_top DESC, sort_score DESC, post_id DESC)');

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
