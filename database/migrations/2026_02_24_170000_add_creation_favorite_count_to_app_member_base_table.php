<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCreationFavoriteCountToAppMemberBaseTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('app_member_base', function (Blueprint $table) {
            $table->integer('creation_count')->default(0)->comment('创作数量')->after('like_count');
            $table->integer('favorite_count')->default(0)->comment('收藏数量')->after('creation_count');
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
            $table->dropColumn(['creation_count', 'favorite_count']);
        });
    }
}
