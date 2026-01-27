<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // 每10分钟刷新帖子排序分（近7天）
        $schedule->command('post:refresh-sort-score')
            ->everyTenMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        // 每天凌晨3点全量刷新
        $schedule->command('post:refresh-sort-score --all')
            ->dailyAt('03:00')
            ->withoutOverlapping()
            ->runInBackground();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
