<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAppChapterContentArticleTable extends Migration
{
    public function up()
    {
        DB::statement("
            CREATE TABLE app_chapter_content_article (
                id int8 NOT NULL GENERATED ALWAYS AS IDENTITY (INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 START 1 CACHE 1),
                chapter_id int8 NOT NULL,
                content_html text NULL,
                images jsonb NOT NULL DEFAULT '[]',
                attachments jsonb NOT NULL DEFAULT '[]',
                word_count int4 NOT NULL DEFAULT 0,
                read_time int4 NOT NULL DEFAULT 0,
                created_at timestamp(0) NULL,
                updated_at timestamp(0) NULL,
                deleted_at timestamp(0) NULL,
                PRIMARY KEY (id)
            )
        ");

        DB::statement("COMMENT ON COLUMN app_chapter_content_article.id IS 'ID'");
        DB::statement("COMMENT ON COLUMN app_chapter_content_article.chapter_id IS '章节ID'");
        DB::statement("COMMENT ON COLUMN app_chapter_content_article.content_html IS '图文内容HTML'");
        DB::statement("COMMENT ON COLUMN app_chapter_content_article.images IS '图片列表'");
        DB::statement("COMMENT ON COLUMN app_chapter_content_article.attachments IS '附件列表'");
        DB::statement("COMMENT ON COLUMN app_chapter_content_article.word_count IS '字数'");
        DB::statement("COMMENT ON COLUMN app_chapter_content_article.read_time IS '预计阅读时间（秒）'");

        DB::statement('CREATE UNIQUE INDEX uk_app_chapter_content_article_chapter_id ON app_chapter_content_article (chapter_id)');
        DB::statement("COMMENT ON TABLE app_chapter_content_article IS '图文课章节内容表'");
    }

    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS app_chapter_content_article');
    }
}
