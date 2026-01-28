<?php

namespace App\Models\Traits;

use App\Services\AppFileUploadService;

/**
 * TOS URL 处理 Trait
 *
 * 提供 URL 字段的自动拼接和提取功能
 * - 读取时：相对路径自动拼接 TOS 域名，完整 URL 直接返回
 * - 写入时：TOS 域名的 URL 提取相对路径，外部 URL 原样保存
 *
 * 使用方式：
 * 1. 在 Model 中 use HasTosUrl
 * 2. 定义 Accessor/Mutator 调用 getTosUrl() 和 setTosUrl()
 *
 * @example
 * class AppAdItem extends Model
 * {
 *     use HasTosUrl;
 *
 *     public function getContentUrlAttribute($value): ?string
 *     {
 *         return $this->getTosUrl($value);
 *     }
 *
 *     public function setContentUrlAttribute($value): void
 *     {
 *         $this->attributes['content_url'] = $this->extractTosPath($value);
 *     }
 * }
 */
trait HasTosUrl
{
    /**
     * 获取完整的 TOS URL
     *
     * @param string|null $value 数据库存储的相对路径或完整 URL
     * @return string|null 完整的 URL
     */
    protected function getTosUrl(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        // 如果已经是完整 URL（外部资源），直接返回
        if (stripos($value, 'http://') === 0 || stripos($value, 'https://') === 0) {
            return $value;
        }

        return (new AppFileUploadService())->generateFileUrl($value);
    }

    /**
     * 提取 TOS 存储路径
     *
     * @param string|null $value 完整 URL 或相对路径
     * @return string|null 相对路径或外部 URL
     */
    protected function extractTosPath(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        // 如果是完整 URL
        if (stripos($value, 'http://') === 0 || stripos($value, 'https://') === 0) {
            $config = config('filesystems.disks.volcengine');
            $schema = $config['schema'] ?? 'https';

            // CDN 域名匹配
            if (!empty($config['url'])) {
                $cdnDomain = $schema . '://' . rtrim($config['url'], '/');
                if (stripos($value, $cdnDomain) === 0) {
                    $path = parse_url($value, PHP_URL_PATH);
                    return $path ? ltrim($path, '/') : $value;
                }
            }

            // Bucket 域名匹配
            if (!empty($config['bucket']) && !empty($config['endpoint'])) {
                $endpoint = preg_replace('/^https?:\/\//', '', $config['endpoint']);
                $bucketDomain = $schema . '://' . $config['bucket'] . '.' . $endpoint;
                if (stripos($value, $bucketDomain) === 0) {
                    $path = parse_url($value, PHP_URL_PATH);
                    return $path ? ltrim($path, '/') : $value;
                }
            }

            // 非 TOS 域名（外部资源），原样保存
            return $value;
        }

        // 相对路径直接返回
        return ltrim($value, '/');
    }
}
