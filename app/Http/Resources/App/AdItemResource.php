<?php

namespace App\Http\Resources\App;

use App\Services\AppFileUploadService;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 广告内容资源 - 用于 App 端广告列表
 */
class AdItemResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            // 广告位ID
            'spaceId' => $this->space_id,
            // 广告ID
            'adId' => $this->ad_id,
            // 广告标题
            // 'adTitle' => $this->ad_title,
            // 广告类型
            // 'adType' => $this->ad_type,
            // 广告内容地址
            'imageUrl' => (new AppFileUploadService())->generateFileUrl($this->content_url),
            // 跳转类型
            'targetType' => $this->target_type,
            // 跳转地址
            'linkUrl' => $this->target_url,
            // 扩展信息
            // 'extJson' => $this->ext_json,
        ];
    }
}
