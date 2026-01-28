<?php

use Illuminate\Database\Migrations\Migration;

class AddPostIpToAppPostBaseTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE app_post_base ADD COLUMN post_ip inet');
        DB::statement("COMMENT ON COLUMN app_post_base.post_ip IS '发文 IP 地址'");
        DB::statement('CREATE INDEX idx_post_ip ON app_post_base (post_ip)');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP INDEX IF EXISTS idx_post_ip');
        DB::statement('ALTER TABLE app_post_base DROP COLUMN IF EXISTS post_ip');
    }
}
