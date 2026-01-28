<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCountFieldsToAppMemberBaseTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('app_member_base', function (Blueprint $table) {
            $table->integer('fans_count')->default(0)->comment('粉丝数')->after('coin');
            $table->integer('following_count')->default(0)->comment('关注数')->after('fans_count');
            $table->integer('like_count')->default(0)->comment('获赞数')->after('following_count');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('app_member_base', function (Blueprint $table) {
            $table->dropColumn(['fans_count', 'following_count', 'like_count']);
        });
    }
}
