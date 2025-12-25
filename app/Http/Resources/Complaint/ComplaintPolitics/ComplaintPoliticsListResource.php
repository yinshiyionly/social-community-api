<?php

declare(strict_types=1);

namespace App\Http\Resources\Complaint\ComplaintPolitics;

use App\Http\Resources\BaseResource;
use App\Models\PublicRelation\ComplaintPolitics;

/**
 * 政治类投诉列表资源
 *
 * 用于政治类投诉列表接口的响应格式化，包含基本信息。
 * 继承 BaseResource 以支持日期格式化。
 */
class ComplaintPoliticsListResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'report_type' => $this->report_type,
            'report_sub_type' => $this->report_sub_type,
            'report_platform' => $this->report_platform,
            'material_id' => $this->material_id,
            'human_name' => $this->human_name,
            'mixed_item_url' => $this->getMixedItemUrl(),
            'mixed_report_platform_name' => $this->getMixedReportPlatformName(),
            'email_config_id' => $this->email_config_id,
            'report_state' => $this->report_state,
            'report_state_label' => $this->report_state_label,
            'created_at' => $this->formatDateTime($this->created_at),
            'completion_time' => $this->formatDateTime($this->completion_time)
        ];
    }

    /**
     * 根据被举报平台类型获取统一的详细地址
     *
     * @return array
     */
    private function getMixedItemUrl(): array
    {
        switch ($this->report_platform) {
            case ComplaintPolitics::REPORT_PLATFORM_WEBSITE:
                return $this->site_url ?? [];
            case ComplaintPolitics::REPORT_PLATFORM_APP:
                return $this->app_url ?? [];
            case ComplaintPolitics::REPORT_PLATFORM_ACCOUNT:
                return $this->account_url ?? [];
            default:
                return [];
        }
    }

    /**
     * 根据被举报平台类型获取统一的平台名称
     *
     * 网站网页 -> site_name
     * APP -> app_name
     * 网络账号 -> account_platform 或 account_platform_name（当需要填写时）
     *
     * @return string
     */
    private function getMixedReportPlatformName(): string
    {
        switch ($this->report_platform) {
            case ComplaintPolitics::REPORT_PLATFORM_WEBSITE:
                return $this->site_name ?? '';
            case ComplaintPolitics::REPORT_PLATFORM_APP:
                return $this->app_name ?? '';
            case ComplaintPolitics::REPORT_PLATFORM_ACCOUNT:
                return $this->getAccountPlatformDisplayName();
            default:
                return '';
        }
    }

    /**
     * 获取网络账号的平台显示名称
     *
     * 如果账号平台需要填写平台名称（博客/直播平台/论坛社区/网盘/音频/其他），
     * 则返回 account_platform_name，否则返回 account_platform
     *
     * @return string
     */
    private function getAccountPlatformDisplayName(): string
    {
        if (ComplaintPolitics::needAccountPlatformName($this->account_platform ?? '')) {
            return $this->account_platform_name ?? '';
        }

        return $this->account_platform ?? '';
    }
}
