<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAppActivitySubmissionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("
            CREATE TABLE app_activity_submission (
                id int8 NOT NULL GENERATED ALWAYS AS IDENTITY (INCREMENT 1 MINVALUE 1 MAXVALUE 9223372036854775807 START 1 CACHE 1),
                activity_id int8 NOT NULL,
                post_id int8 NOT NULL,
                member_id int8 NOT NULL,
                is_featured int2 NOT NULL DEFAULT 0,
                is_winner int2 NOT NULL DEFAULT 0,
                status int2 NOT NULL DEFAULT 0,
                created_at timestamp(0) NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at timestamp(0) NULL,
                PRIMARY KEY (id)
            )
        ");

        // 列注释
        DB::statement("COMMENT ON COLUMN app_activity_submission.id IS 'ID'");
        DB::statement("COMMENT ON COLUMN app_activity_submission.activity_id IS '活动ID'");
        DB::statement("COMMENT ON COLUMN app_activity_submission.post_id IS '投稿帖子ID'");
        DB::statement("COMMENT ON COLUMN app_activity_submission.member_id IS '投稿人ID'");
        DB::statement("COMMENT ON COLUMN app_activity_submission.is_featured IS '是否精选：0=否 1=是'");
        DB::statement("COMMENT ON COLUMN app_activity_submission.is_winner IS '是否获奖：0=否 1=是'");
        DB::statement("COMMENT ON COLUMN app_activity_submission.status IS '状态：0=待审核 1=已通过 2=已拒绝'");

        // 唯一约束：同一活动同一帖子只能投稿一次
        DB::statement('CREATE UNIQUE INDEX uk_app_activity_submission_activity_post ON app_activity_submission (activity_id, post_id)');

        // 查询索引
        DB::statement('CREATE INDEX idx_app_activity_submission_activity_created ON app_activity_submission (activity_id, created_at DESC)');
        DB::statement('CREATE INDEX idx_app_activity_submission_member ON app_activity_submission (member_id, activity_id)');
        DB::statement('CREATE INDEX idx_app_activity_submission_post_id ON app_activity_submission (post_id)');
        DB::statement('CREATE INDEX idx_app_activity_submission_activity_featured ON app_activity_submission (activity_id, is_featured) WHERE is_featured = 1');

        // 表注释
        DB::statement("COMMENT ON TABLE app_activity_submission IS '活动投稿关联表'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP TABLE IF EXISTS app_activity_submission');
    }
}
