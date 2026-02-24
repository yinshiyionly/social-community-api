<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddIsOfficialToAppMemberBaseTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('app_member_base', function (Blueprint $table) {
            $table->smallInteger('is_official')->default(0)->after('status');
            $table->string('official_label', 50)->default('')->after('is_official');
        });

        DB::statement("COMMENT ON COLUMN app_member_base.is_official IS '是否官方账号：0=否 1=是'");
        DB::statement("COMMENT ON COLUMN app_member_base.official_label IS '官方标签（如：官方、认证等）'");

        DB::statement('CREATE INDEX idx_app_member_base_is_official ON app_member_base (is_official) WHERE is_official = 1');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP INDEX IF EXISTS idx_app_member_base_is_official');

        Schema::table('app_member_base', function (Blueprint $table) {
            $table->dropColumn(['is_official', 'official_label']);
        });
    }
}
