<?php

namespace App\Services\App;

use App\Models\App\AppMemberBase;
use App\Models\App\AppPostBase;
use App\Http\Resources\App\MemberSearchResource;
use App\Http\Resources\App\PostSearchResource;

/**
 * 搜索服务类
 */
class SearchService
{
    /**
     * 默认搜索结果数量限制
     */
    const DEFAULT_LIMIT = 20;

    /**
     * 执行搜索
     *
     * @param string $keyword 搜索关键词
     * @param string $source 搜索来源 (all|member|post)
     * @return array
     */
    public function search(string $keyword, string $source): array
    {
        $result = [];

        if ($source === 'all' || $source === 'member') {
            $result['members'] = $this->searchMembers($keyword);
        }

        if ($source === 'all' || $source === 'post') {
            $result['posts'] = $this->searchPosts($keyword);
        }

        return $result;
    }

    /**
     * 搜索会员
     *
     * @param string $keyword 搜索关键词
     * @param int $limit 结果数量限制
     * @return array
     */
    public function searchMembers(string $keyword, int $limit = self::DEFAULT_LIMIT): array
    {
        $members = AppMemberBase::normal()
            ->where('nickname', 'ILIKE', '%' . $keyword . '%')
            ->orderByDesc('member_id')
            ->limit($limit)
            ->get();

        $service = $this;
        return MemberSearchResource::collection($members)
            ->map(function ($resource) use ($keyword, $service) {
                $data = $resource->resolve();
                $data['nickname'] = $service->highlightKeyword($data['nickname'], $keyword);
                return $data;
            })
            ->toArray();
    }

    /**
     * 搜索帖子
     *
     * @param string $keyword 搜索关键词
     * @param int $limit 结果数量限制
     * @return array
     */
    public function searchPosts(string $keyword, int $limit = self::DEFAULT_LIMIT): array
    {
        $posts = AppPostBase::approved()
            ->visible()
            ->where('title', 'ILIKE', '%' . $keyword . '%')
            ->orderByDesc('post_id')
            ->limit($limit)
            ->get();

        $service = $this;
        return PostSearchResource::collection($posts)
            ->map(function ($resource) use ($keyword, $service) {
                $data = $resource->resolve();
                $data['title'] = $service->highlightKeyword($data['title'], $keyword);
                return $data;
            })
            ->toArray();
    }

    /**
     * 关键词高亮处理
     *
     * @param string $text 原始文本
     * @param string $keyword 关键词
     * @return string 高亮后的文本
     */
    public function highlightKeyword(string $text, string $keyword): string
    {
        if (empty($keyword) || empty($text)) {
            return $text;
        }

        // 转义 HTML 特殊字符防止 XSS
        $escapedText = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        $escapedKeyword = htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8');

        // 转义正则特殊字符
        $pattern = '/' . preg_quote($escapedKeyword, '/') . '/iu';

        // 使用 <em> 标签包裹匹配的关键词
        return preg_replace($pattern, '<em>$0</em>', $escapedText);
    }
}
