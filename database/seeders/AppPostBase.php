<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AppPostBase extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        for ($i = 1; $i < 20; $i++) {
            DB::table('app_post_base')->insert([
                'member_id' =>  3545623190,
                'post_type' => 2,
                'title' => '冬日之约-跟着杨老师一起爬山' . $i,
                'content' => '冬日之约-跟着杨老师一起爬山' . $i,
                'media_data' => json_encode([
                    [
                        'url' => '3545623190/video/20260112/92bcddc7-090a-488b-b4d4-ce5dd293d786.mp4',
                        'type' => 'video'
                    ]
                ])
            ]);
        }
    }
}
