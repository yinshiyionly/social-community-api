<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSortScoreToAppPostBaseTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('app_post_base', function (Blueprint $table) {
            $table->decimal('sort_score', 16, 6)->default(0)->comment('排序分（物化计算）')->after('is_top');
            $table->index(['status', 'visible', 'is_top', 'sort_score', 'post_id'], 'idx_post_list_sort');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('app_post_base', function (Blueprint $table) {
            $table->dropIndex('idx_post_list_sort');
            $table->dropColumn('sort_score');
        });
    }
}
