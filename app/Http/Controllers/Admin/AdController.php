<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdListRequest;
use App\Http\Resources\Admin\AdPreviewItemResource;
use App\Http\Resources\ApiResponse;
use App\Models\App\AppAdItem;
use App\Models\App\AppAdSpace;
use App\Services\App\AdService;
use Illuminate\Support\Facades\Log;

/**
 * Admin 广告模块通用控制器。
 *
 * 职责：
 * 1. 提供后台广告预览列表与表单常量接口；
 * 2. 统一使用 Admin 响应结构，避免复用 App 控制器导致返回协议不一致；
 * 3. 异常场景统一记录日志并返回通用错误，避免泄露内部异常细节。
 */
class AdController extends Controller
{
    /**
     * @var AdService
     */
    protected $adService;

    /**
     * AdController constructor.
     *
     * @param AdService $adService
     */
    public function __construct(AdService $adService)
    {
        $this->adService = $adService;
    }

    /**
     * 获取广告预览列表。
     *
     * 接口用途：
     * - 供后台按广告位编码和平台预览实际生效的广告内容；
     * - 返回字段与 App 端广告字段保持一致，便于后台联调验证投放结果。
     *
     * 关键输入：
     * - spaceCode：广告位编码（必填）；
     * - platform：平台类型（可选，默认全平台）。
     *
     * @param AdListRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function list(AdListRequest $request)
    {
        $spaceCode = $request->input('spaceCode');
        $platform = (int) $request->input('platform', AppAdSpace::PLATFORM_ALL);

        try {
            $adItems = $this->adService->getAdList($spaceCode, $platform);

            return ApiResponse::collection($adItems, AdPreviewItemResource::class, '查询成功');
        } catch (\Exception $e) {
            Log::error('获取 Admin 广告预览列表失败', [
                'action' => 'list',
                'space_code' => $spaceCode,
                'platform' => $platform,
                'error' => $e->getMessage(),
            ]);

            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 获取广告模块常量选项。
     *
     * 常量来源：
     * 1. platform/status 来自 `app_ad_space` 业务枚举；
     * 2. adType/targetType/status 来自 `app_ad_item` 业务枚举。
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function constants()
    {
        $data = [
            /*'platformOptions' => [
                ['label' => '全平台', 'value' => AppAdSpace::PLATFORM_ALL],
                ['label' => 'iOS', 'value' => AppAdSpace::PLATFORM_IOS],
                ['label' => 'Android', 'value' => AppAdSpace::PLATFORM_ANDROID],
            ],*/
            'spaceStatusOptions' => [
                ['label' => '启用', 'value' => AppAdSpace::STATUS_ENABLED],
                ['label' => '禁用', 'value' => AppAdSpace::STATUS_DISABLED],
            ],
            'adTypeOptions' => [
                ['label' => '图片', 'value' => AppAdItem::AD_TYPE_IMAGE],
                /*['label' => '视频', 'value' => AppAdItem::AD_TYPE_VIDEO],
                ['label' => '文本', 'value' => AppAdItem::AD_TYPE_TEXT],
                ['label' => 'HTML', 'value' => AppAdItem::AD_TYPE_HTML],*/
            ],
            'targetTypeOptions' => [
                /*['label' => '外部链接', 'value' => AppAdItem::TARGET_TYPE_EXTERNAL],*/
                ['label' => '内部页面', 'value' => AppAdItem::TARGET_TYPE_INTERNAL],
                /*['label' => '不跳转', 'value' => AppAdItem::TARGET_TYPE_NONE],*/
            ],
            'adStatusOptions' => [
                ['label' => '上线', 'value' => AppAdItem::STATUS_ONLINE],
                ['label' => '下线', 'value' => AppAdItem::STATUS_OFFLINE],
            ],
        ];

        return ApiResponse::success(['data' => $data]);
    }
}
