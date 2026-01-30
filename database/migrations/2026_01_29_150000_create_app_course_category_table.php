<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateAppCourseCategoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('app_course_category', function (Blueprint $table) {
            $table->increments('category_id')->comment('分类ID');
            $table->integer('parent_id')->default(0)->comment('父分类ID');
            $table->string('category_name', 50)->comment('分类名称');
            $table->string('category_code', 50)->comment('分类编码：yoga/tea/calligraphy等');
            $table->string('icon', 255)->nullable()->comment('分类图标');
            $table->string('cover', 255)->nullable()->comment('分类封面');
            $table->string('description', 500)->nullable()->comment('分类描述');
            $table->integer('sort_order')->default(0)->comment('排序');
            $table->smallInteger('status')->default(1)->comment('状态：1启用 2禁用');
            $table->string('create_by', 64)->nullable();
            $table->timestamp('create_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->string('update_by', 64)->nullable();
            $table->timestamp('update_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->smallInteger('del_flag')->default(0)->comment('删除标志：0正常 1删除');

            $table->unique(['category_code', 'del_flag'], 'uk_category_code');
            $table->index('parent_id', 'idx_category_parent');
            $table->index('status', 'idx_category_status');
        });

        DB::statement("COMMENT ON TABLE app_course_category IS '课程分类表'");

        // 初始化分类数据
        DB::table('app_course_category')->insert([
            ['category_code' => 'yoga', 'category_name' => '瑜伽课', 'sort_order' => 1],
            ['category_code' => 'tea', 'category_name' => '茶艺课', 'sort_order' => 2],
            ['category_code' => 'calligraphy', 'category_name' => '书法课', 'sort_order' => 3],
            ['category_code' => 'painting', 'category_name' => '绘画课', 'sort_order' => 4],
            ['category_code' => 'music', 'category_name' => '音乐课', 'sort_order' => 5],
            ['category_code' => 'fitness', 'category_name' => '健身课', 'sort_order' => 6],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('app_course_category');
    }
}
