<?php

namespace App\Console\Commands;

use App\Services\BaijiayunLiveService;
use Illuminate\Console\Command;

class GetBaiJiaYunVideoList extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'a:a';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $videoList = $this->getData();
        $this->info('拉取完成，共获取视频 ' . count($videoList) . ' 条');

        return 0;
    }

    public function getData(): array
    {
        $pageSize = 10;
        $page = 1;
        $total = 0;
        $allVideos = [];

        $service = new BaijiayunLiveService();

        while (true) {
            $result = $service->getVideoList($page, $pageSize);
            if (!($result['success'] ?? false)) {
                $this->error(sprintf(
                    '第 %d 页拉取失败，错误码：%s，错误信息：%s',
                    $page,
                    $result['error_code'] ?? 'UNKNOWN',
                    $result['error_message'] ?? 'UNKNOWN'
                ));
                break;
            }

            $list = $result['data']['list'] ?? [];
            dd(
                $list
            );
            $total = (int)($result['data']['total'] ?? $total);

            if (empty($list)) {
                $this->warn("第 {$page} 页返回空数据，提前结束");
                break;
            }

            $allVideos = array_merge($allVideos, $list);
            $this->info("第 {$page} 页拉取成功，本页 " . count($list) . " 条，累计 " . count($allVideos) . "/{$total}");

            // 终止条件：达到总数，或本页不足 pageSize（最后一页）
            if (($total > 0 && count($allVideos) >= $total) || count($list) < $pageSize) {
                break;
            }

            $page++;
        }

        return $allVideos;
    }
}
