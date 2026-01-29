<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateAppCourseCertificateTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 证书模板表
        Schema::create('app_certificate_template', function (Blueprint $table) {
            $table->bigIncrements('template_id')->comment('模板ID');
            $table->string('template_name', 100)->comment('模板名称');
            $table->string('template_image', 500)->comment('模板背景图');
            $table->jsonb('template_config')->default('{}')->comment('模板配置（文字位置、字体等）');
            $table->smallInteger('status')->default(1)->comment('状态：1启用 2禁用');
            $table->timestamp('create_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('update_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
        });
        DB::statement("COMMENT ON TABLE app_certificate_template IS '证书模板表'");

        // 课程证书配置表
        Schema::create('app_course_certificate', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('ID');
            $table->bigInteger('course_id')->unique()->comment('课程ID');
            $table->bigInteger('template_id')->comment('证书模板ID');
            $table->string('certificate_title', 200)->comment('证书标题');
            $table->text('certificate_content')->nullable()->comment('证书内容');
            $table->smallInteger('issue_condition')->default(1)->comment('发放条件：1完课 2完课+作业 3手动发放');
            $table->decimal('min_progress', 5, 2)->default(100)->comment('最低完课进度%');
            $table->integer('min_homework')->default(0)->comment('最低作业完成数');
            $table->smallInteger('status')->default(1)->comment('状态：1启用 2禁用');
            $table->timestamp('create_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('update_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
        });
        DB::statement("COMMENT ON TABLE app_course_certificate IS '课程证书配置表'");

        // 用户证书表
        Schema::create('app_member_certificate', function (Blueprint $table) {
            $table->bigIncrements('cert_id')->comment('证书ID');
            $table->string('cert_no', 64)->unique()->comment('证书编号');
            $table->bigInteger('member_id')->comment('用户ID');
            $table->bigInteger('course_id')->comment('课程ID');
            $table->bigInteger('template_id')->comment('模板ID');
            $table->string('member_name', 50)->comment('用户姓名');
            $table->string('course_title', 200)->comment('课程名称');
            $table->string('cert_image', 500)->nullable()->comment('证书图片');
            $table->decimal('final_progress', 5, 2)->default(0)->comment('最终进度');
            $table->integer('final_homework')->default(0)->comment('完成作业数');
            $table->timestamp('issue_time')->nullable()->comment('发放时间');
            $table->smallInteger('status')->default(1)->comment('状态：1有效 2已撤销');
            $table->timestamp('create_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));

            $table->unique(['member_id', 'course_id'], 'uk_member_course_cert');
            $table->index('member_id', 'idx_cert_member');
            $table->index('course_id', 'idx_cert_course');
        });
        DB::statement("COMMENT ON TABLE app_member_certificate IS '用户证书表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('app_member_certificate');
        Schema::dropIfExists('app_course_certificate');
        Schema::dropIfExists('app_certificate_template');
    }
}
