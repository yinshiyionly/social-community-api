<?php

namespace App\Services\App;

use App\Models\App\AppFileRecord;
use App\Models\Traits\HasTosUrl;

/**
 * 文章内容媒体解析器
 *
 * 功能：
 * 1. 扫描 content 中的媒体标签 URL
 * 2. 将项目 CDN/TOS 绝对 URL 替换为相对路径
 * 3. 提取媒体列表生成 media_data
 * 4. 提取首个图片/视频生成 cover
 */
class ArticleContentMediaParser
{
    use HasTosUrl;

    /**
     * 图片后缀
     */
    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'];

    /**
     * 视频后缀
     */
    private const VIDEO_EXTENSIONS = ['mp4', 'mov', 'avi', 'wmv', 'flv', 'mkv', 'webm', 'm4v'];

    /**
     * 音频后缀
     */
    private const AUDIO_EXTENSIONS = ['mp3', 'wav', 'ogg', 'flac', 'aac', 'm4a'];

    /**
     * 解析并归一化文章内容
     *
     * @param string $content
     * @return array{content:string,media_data:array,cover:array}
     */
    public function parse(string $content): array
    {
        if ($content === '') {
            return [
                'content' => '',
                'media_data' => [],
                'cover' => [],
            ];
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $wrappedHtml = '<div id="article-content-root">' . $content . '</div>';

        $previous = libxml_use_internal_errors(true);
        $loaded = $dom->loadHTML('<?xml encoding="UTF-8" ?>' . $wrappedHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$loaded) {
            return [
                'content' => $content,
                'media_data' => [],
                'cover' => [],
            ];
        }

        $root = $dom->getElementsByTagName('div')->item(0);
        if (!$root) {
            return [
                'content' => $content,
                'media_data' => [],
                'cover' => [],
            ];
        }

        $mediaData = $this->collectAndReplaceMedia($dom);
        $this->enrichMediaDataWithFileRecord($mediaData);

        return [
            'content' => $this->getInnerHtml($root),
            'media_data' => $mediaData,
            'cover' => $this->resolveCover($mediaData),
        ];
    }

    /**
     * 扫描媒体标签并替换 URL
     *
     * @param \DOMDocument $dom
     * @return array
     */
    private function collectAndReplaceMedia(\DOMDocument $dom): array
    {
        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->query('//*[(self::img or self::video or self::audio or self::source) and (@src or @poster)]');

        if (!$nodes) {
            return [];
        }

        $mediaData = [];
        $seen = [];

        /** @var \DOMElement $node */
        foreach ($nodes as $node) {
            $tag = strtolower($node->nodeName);

            foreach (['src', 'poster'] as $attribute) {
                if (!$node->hasAttribute($attribute)) {
                    continue;
                }

                $originUrl = trim(html_entity_decode($node->getAttribute($attribute), ENT_QUOTES, 'UTF-8'));
                if ($originUrl === '' || $this->isIgnoredScheme($originUrl)) {
                    continue;
                }

                $normalizedUrl = $this->normalizeUrl($originUrl);
                if ($normalizedUrl === '') {
                    continue;
                }

                $node->setAttribute($attribute, $normalizedUrl);

                // poster 只替换，不作为媒体数据提取来源
                if ($attribute !== 'src') {
                    continue;
                }

                if (!$this->shouldCollectMedia($originUrl, $normalizedUrl)) {
                    continue;
                }

                $mediaType = $this->resolveMediaType($tag, $node, $normalizedUrl);
                if ($mediaType === null) {
                    continue;
                }

                $dedupKey = $mediaType . '|' . $normalizedUrl;
                if (isset($seen[$dedupKey])) {
                    continue;
                }
                $seen[$dedupKey] = true;

                $mediaData[] = [
                    'url' => $normalizedUrl,
                    'type' => $mediaType,
                ];
            }
        }

        return $mediaData;
    }

    /**
     * 补齐媒体元数据（宽高、时长、视频封面）
     *
     * @param array $mediaData
     * @return void
     */
    private function enrichMediaDataWithFileRecord(array &$mediaData): void
    {
        if (empty($mediaData)) {
            return;
        }

        $pathToIndexes = [];
        foreach ($mediaData as $index => $item) {
            if (empty($item['url']) || $this->isAbsoluteHttpUrl($item['url'])) {
                continue;
            }

            $path = $this->extractPath($item['url']);
            if ($path === null) {
                continue;
            }

            $pathToIndexes[$path][] = $index;
        }

        if (empty($pathToIndexes)) {
            return;
        }

        try {
            $records = AppFileRecord::query()
                ->select(['file_path', 'mime_type', 'width', 'height', 'duration', 'extra'])
                ->whereIn('file_path', array_keys($pathToIndexes))
                ->get();
        } catch (\Throwable $e) {
            // 文件记录查询失败时不影响主流程，仅保留基础 url/type
            return;
        }

        foreach ($records as $record) {
            $indexes = $pathToIndexes[$record->file_path] ?? [];
            if (empty($indexes)) {
                continue;
            }

            foreach ($indexes as $index) {
                if (empty($mediaData[$index])) {
                    continue;
                }

                $mediaData[$index]['width'] = (int)($record->width ?? 0);
                $mediaData[$index]['height'] = (int)($record->height ?? 0);

                if ($this->startsWith($record->mime_type, 'video/')) {
                    $mediaData[$index]['type'] = 'video';
                    $mediaData[$index]['duration'] = (int)($record->duration ?? 0);

                    $cover = is_array($record->extra) ? ($record->extra['cover'] ?? '') : '';
                    if ($cover !== '') {
                        $mediaData[$index]['cover'] = ltrim($cover, '/');
                    }
                } elseif ($this->startsWith($record->mime_type, 'audio/')) {
                    $mediaData[$index]['type'] = 'audio';
                    $mediaData[$index]['duration'] = (int)($record->duration ?? 0);
                } elseif ($this->startsWith($record->mime_type, 'image/')) {
                    $mediaData[$index]['type'] = 'image';
                }
            }
        }
    }

    /**
     * 生成封面
     *
     * @param array $mediaData
     * @return array
     */
    private function resolveCover(array $mediaData): array
    {
        foreach ($mediaData as $item) {
            $type = $item['type'] ?? '';
            if (!in_array($type, ['image', 'video'], true)) {
                continue;
            }

            $coverUrl = $item['url'] ?? '';
            if ($type === 'video' && !empty($item['cover'])) {
                $coverUrl = $item['cover'];
            }

            if ($coverUrl === '') {
                continue;
            }

            return [
                'url' => $coverUrl,
                'width' => (int)($item['width'] ?? 0),
                'height' => (int)($item['height'] ?? 0),
            ];
        }

        return [];
    }

    /**
     * 归一化 URL
     *
     * @param string $url
     * @return string
     */
    private function normalizeUrl(string $url): string
    {
        $path = $this->extractTosPath($url);
        if ($path === null) {
            return '';
        }

        return $path;
    }

    /**
     * 是否需要收集到 media_data
     *
     * @param string $originUrl
     * @param string $normalizedUrl
     * @return bool
     */
    private function shouldCollectMedia(string $originUrl, string $normalizedUrl): bool
    {
        if ($normalizedUrl === '' || $this->isIgnoredScheme($normalizedUrl)) {
            return false;
        }

        // 绝对 URL 仅收集项目 CDN/TOS 域名（替换后变成相对路径）
        if ($this->isAbsoluteHttpUrl($originUrl)) {
            return !$this->isAbsoluteHttpUrl($normalizedUrl);
        }

        // 已经是相对路径时也允许提取
        return true;
    }

    /**
     * 解析媒体类型
     *
     * @param string $tag
     * @param \DOMElement $node
     * @param string $url
     * @return string|null
     */
    private function resolveMediaType(string $tag, \DOMElement $node, string $url): ?string
    {
        if ($tag === 'img') {
            return 'image';
        }

        if ($tag === 'video') {
            return 'video';
        }

        if ($tag === 'audio') {
            return 'audio';
        }

        if ($tag === 'source') {
            $parentTag = '';
            if ($node->parentNode instanceof \DOMElement) {
                $parentTag = strtolower($node->parentNode->nodeName);
            }

            if ($parentTag === 'video') {
                return 'video';
            }

            if ($parentTag === 'audio') {
                return 'audio';
            }
        }

        $ext = $this->getUrlExtension($url);
        if (in_array($ext, self::IMAGE_EXTENSIONS, true)) {
            return 'image';
        }

        if (in_array($ext, self::VIDEO_EXTENSIONS, true)) {
            return 'video';
        }

        if (in_array($ext, self::AUDIO_EXTENSIONS, true)) {
            return 'audio';
        }

        return null;
    }

    /**
     * 获取 URL 后缀
     *
     * @param string $url
     * @return string
     */
    private function getUrlExtension(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (!$path) {
            return '';
        }

        return strtolower(pathinfo($path, PATHINFO_EXTENSION));
    }

    /**
     * 提取路径
     *
     * @param string $url
     * @return string|null
     */
    private function extractPath(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (is_string($path) && $path !== '') {
            return ltrim($path, '/');
        }

        if ($this->isAbsoluteHttpUrl($url)) {
            return null;
        }

        return ltrim($url, '/');
    }

    /**
     * 是否绝对 HTTP URL
     *
     * @param string $url
     * @return bool
     */
    private function isAbsoluteHttpUrl(string $url): bool
    {
        return stripos($url, 'http://') === 0 || stripos($url, 'https://') === 0;
    }

    /**
     * 忽略的 URL scheme
     *
     * @param string $url
     * @return bool
     */
    private function isIgnoredScheme(string $url): bool
    {
        return stripos($url, 'data:') === 0
            || stripos($url, 'blob:') === 0
            || stripos($url, 'javascript:') === 0;
    }

    /**
     * 前缀匹配（兼容 PHP 7）
     *
     * @param string|null $value
     * @param string $prefix
     * @return bool
     */
    private function startsWith(?string $value, string $prefix): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        return stripos($value, $prefix) === 0;
    }

    /**
     * 获取节点 innerHTML
     *
     * @param \DOMElement $element
     * @return string
     */
    private function getInnerHtml(\DOMElement $element): string
    {
        $html = '';
        foreach ($element->childNodes as $childNode) {
            $html .= $element->ownerDocument->saveHTML($childNode);
        }

        return $html;
    }
}
