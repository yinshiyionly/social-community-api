<?php

namespace App\Console\Commands;

use App\Models\App\AppLiveRoom;
use App\Services\BaijiayunLiveService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MaintainBaiJiaYunLiveStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'baijiayun:maintain-live-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '定时维护百家云直播状态';

    public function handle()
    {
        // 1. 获取本地数据
        $liveData = $this->getLocalData();
        $this->info('查询完成，共获取直播间数据 ' . count($liveData) . ' 条');

        // 2. 无数据 提前返回
        if (empty($liveData)) {
            return 0;
        }

        // 3. 循环处理 liveData
        $result = $this->getLiveStatusByRemote($liveData);


        $this->info(sprintf(
            '执行完成：成功 %d 条，失败 %d 条',
            $result[0],
            $result[1]
        ));

        return 0;
    }

    /**
     * @param array $liveData
     * @return int[]
     */
    protected function getLiveStatusByRemote(array $liveData)
    {
        $successCnt = $failCnt = 0;

        $service = $this->createBaijiayunLiveService();

        foreach ($liveData as $liveItem) {
            // 1. 调用百家云接口获取直播教室上课状态
            $liveStatus = $service->liveGetLiveStatus($liveItem['third_party_room_id']);
            if (isset($liveStatus['success'])
                && $liveStatus['success'] === true
                && isset($liveStatus['data']['status'])
                && $liveStatus['data']['status'] == 0
            ) {
                // 2. 更新本地直播间 live_status 状态
                try {
                    AppLiveRoom::query()
                        ->where('third_party_room_id', $liveItem['third_party_room_id'])
                        ->update(['live_status' => AppLiveRoom::LIVE_STATUS_ENDED]);
                    $successCnt++;
                } catch (\Exception $e) {
                    $failCnt++;
                    Log::channel('console')->error("更新直播间状态失败", [
                        'third_party_room_id' => $liveItem['third_party_room_id'] ?? null,
                        'room_title'          => $liveItem['room_title'] ?? null,
                        'error'               => $e->getMessage(),
                        'file'                => $e->getFile(),
                        'line'                => $e->getLine()
                    ]);
                }
            }
        }

        return [$successCnt, $failCnt];
    }

    /**
     * 获取本地数据
     * 说明:
     * - 获取 app_live_room 数据表中正在直播且直播结束时间小于当前时间的所有直播间数据
     *
     * @return array
     */
    protected function getLocalData(): array
    {
        return AppLiveRoom::query()
            ->select(['room_id', 'room_title', 'third_party_room_id', 'scheduled_end_time', 'live_status'])
            ->where('live_status', AppLiveRoom::LIVE_STATUS_LIVING)
            ->where('scheduled_end_time', '<', Carbon::now()->toDateTimeString())
            ->get()
            ->toArray();
    }

    /**
     * 创建百家云服务实例。
     *
     * 说明：
     * - 单独抽取工厂方法，便于测试阶段替换为桩服务。
     *
     * @return BaijiayunLiveService
     */
    protected function createBaijiayunLiveService(): BaijiayunLiveService
    {
        return new BaijiayunLiveService();
    }
}
