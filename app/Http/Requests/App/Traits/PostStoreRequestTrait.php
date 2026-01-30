<?php

namespace App\Http\Requests\App\Traits;

use App\Models\App\AppPostBase;

/**
 * 发表帖子请求公共验证 Trait
 *
 * 提供媒体类型验证和默认值处理的公共方法
 */
trait PostStoreRequestTrait
{
    /**
     * 允许的图片后缀
     *
     * @var array
     */
    protected static $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];

    /**
     * 允许的视频后缀
     *
     * @var array
     */
    protected static $videoExtensions = ['mp4', 'mov', 'avi', 'wmv', 'flv', 'mkv', 'webm'];

    /**
     * 公共验证规则
     *
     * @return array
     */
    protected function commonRules(): array
    {
        return [
            'title' => 'sometimes|nullable|string|max:50',
            'cover' => 'sometimes|nullable|string|max:500',
            'image_show_style' => 'sometimes|integer|in:1,2',
            'article_cover_style' => 'sometimes|integer|in:1,2,3',
            'visible' => 'sometimes|integer|in:0,1',
        ];
    }

    /**
     * 公共错误消息
     *
     * @return array
     */
    protected function commonMessages(): array
    {
        return [
            'title.max' => '标题最多50字',
            'cover.max' => '封面URL过长',
            'image_show_style.in' => '图片展示样式不正确',
            'article_cover_style.in' => '文章封面样式不正确',
        ];
    }

    /**
     * 判断 URL 是否为图片
     *
     * @param string $url
     * @return bool
     */
    protected function isImageUrl(string $url): bool
    {
        $ext = $this->getUrlExtension($url);
        return in_array($ext, self::$imageExtensions);
    }

    /**
     * 判断 URL 是否为视频
     *
     * @param string $url
     * @return bool
     */
    protected function isVideoUrl(string $url): bool
    {
        $ext = $this->getUrlExtension($url);
        return in_array($ext, self::$videoExtensions);
    }

    /**
     * 获取 URL 的文件后缀
     *
     * @param string $url
     * @return string
     */
    protected function getUrlExtension(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (!$path) {
            return '';
        }
        return strtolower(pathinfo($path, PATHINFO_EXTENSION));
    }

    /**
     * 应用默认值
     *
     * @param array $data 验证后的数据
     * @param int $postType 帖子类型
     * @return array
     */
    protected function applyDefaults(array $data, int $postType): array
    {
        $data['post_type'] = $postType;
        $data['title'] = $data['title'] ?? '';
        $data['content'] = $data['content'] ?? '';
        $data['image_show_style'] = $data['image_show_style'] ?? AppPostBase::IMAGE_SHOW_STYLE_LARGE;
        $data['article_cover_style'] = $data['article_cover_style'] ?? AppPostBase::ARTICLE_COVER_STYLE_SINGLE;
        $data['visible'] = $data['visible'] ?? AppPostBase::VISIBLE_PUBLIC;

        // media_data: 将 URL 字符串数组转为对象数组
        if (!empty($data['media_data'])) {
            $data['media_data'] = array_map(function ($url) {
                return ['url' => $url];
            }, $data['media_data']);
        } else {
            $data['media_data'] = [];
        }

        // cover: 将 URL 字符串转为对象
        if (!empty($data['cover'])) {
            $data['cover'] = ['url' => $data['cover']];
        } else {
            $data['cover'] = [];
        }

        return $data;
    }
}
