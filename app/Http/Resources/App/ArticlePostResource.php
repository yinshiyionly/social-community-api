<?php

namespace App\Http\Resources\App;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * App 文章详情资源（blocks 渲染协议）。
 */
class ArticlePostResource extends JsonResource
{
    private const DEFAULT_AVATAR = '';
    private const DEFAULT_NICKNAME = '用户';

    /**
     * 输出文章详情。
     *
     * 字段约定：
     * - blocks 为详情页渲染唯一内容源，保持与发布顺序一致；
     * - topics 输出为 {id,name}，用于详情页标签展示；
     * - isLiked/isFavorited 由 AppApiResponse::resource 的 extra 合并注入。
     *
     * @param \Illuminate\Http\Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $blocks = $this->resolveBlocks();

        return [
            'id' => (int)$this->post_id,
            'postType' => (int)$this->post_type,
            'title' => (string)($this->title ?? ''),
            'blocks' => $blocks,
            'topics' => $this->resolveTopics(),
            'coverImage' => $this->resolveCoverImage($blocks),
            'createTime' => $this->created_at ? $this->created_at->toIso8601String() : null,
            'author' => $this->formatAuthor(),
            'likeCount' => $this->stat ? (int)$this->stat->like_count : 0,
            'commentCount' => $this->stat ? (int)$this->stat->comment_count : 0,
            'favoriteCount' => $this->stat ? (int)$this->stat->collection_count : 0,
        ];
    }

    /**
     * 归一化 blocks 输出。
     *
     * @return array<int, array<string, string>>
     */
    protected function resolveBlocks(): array
    {
        $mediaData = $this->media_data ?? [];
        if (!is_array($mediaData)) {
            return [];
        }

        $blocks = [];

        foreach ($mediaData as $item) {
            if (!is_array($item)) {
                continue;
            }

            $type = (string)($item['type'] ?? '');

            if ($type === 'text') {
                $blocks[] = [
                    'type' => 'text',
                    'text' => (string)($item['text'] ?? ''),
                ];
                continue;
            }

            if ($type === 'image') {
                $url = (string)($item['url'] ?? '');
                if ($url === '') {
                    continue;
                }

                $blocks[] = [
                    'type' => 'image',
                    'url' => $url,
                ];
                continue;
            }

            if ($type === 'video') {
                $url = (string)($item['url'] ?? '');
                if ($url === '') {
                    continue;
                }

                $videoBlock = [
                    'type' => 'video',
                    'url' => $url,
                ];

                $poster = (string)($item['poster'] ?? ($item['cover'] ?? ''));
                if ($poster !== '') {
                    $videoBlock['poster'] = $poster;
                }

                $blocks[] = $videoBlock;
            }
        }

        return $blocks;
    }

    /**
     * 解析话题标签。
     *
     * @return array<int, array{id:int,name:string}>
     */
    protected function resolveTopics(): array
    {
        $topics = $this->topics ?? [];
        if ($topics instanceof Collection) {
            $topics = $topics->all();
        }

        if (!is_array($topics)) {
            return [];
        }

        $result = [];
        foreach ($topics as $topic) {
            $topicId = is_array($topic)
                ? (int)($topic['topic_id'] ?? 0)
                : (int)($topic->topic_id ?? 0);
            if ($topicId <= 0) {
                continue;
            }

            $result[] = [
                'id' => $topicId,
                'name' => is_array($topic)
                    ? (string)($topic['topic_name'] ?? '')
                    : (string)($topic->topic_name ?? ''),
            ];
        }

        return $result;
    }

    /**
     * 解析封面图。
     *
     * 兜底顺序：
     * 1. 帖子 cover.url；
     * 2. blocks 第一张 image；
     * 3. blocks 第一个 video 的 poster。
     *
     * @param array<int, array<string, string>> $blocks
     * @return string
     */
    protected function resolveCoverImage(array $blocks): string
    {
        $cover = $this->cover ?? [];
        if (is_array($cover) && !empty($cover['url'])) {
            return (string)$cover['url'];
        }

        foreach ($blocks as $block) {
            if (($block['type'] ?? '') === 'image' && !empty($block['url'])) {
                return (string)$block['url'];
            }
        }

        foreach ($blocks as $block) {
            if (($block['type'] ?? '') === 'video' && !empty($block['poster'])) {
                return (string)$block['poster'];
            }
        }

        return '';
    }

    /**
     * 格式化作者信息
     *
     * @return array<string, int|string>
     */
    protected function formatAuthor(): array
    {
        $member = $this->member;

        if (!$member) {
            return [
                'memberId' => (int)$this->member_id,
                'nickname' => self::DEFAULT_NICKNAME,
                'avatar' => self::DEFAULT_AVATAR,
            ];
        }

        return [
            'memberId' => (int)$member->member_id,
            'nickname' => $member->nickname ?? self::DEFAULT_NICKNAME,
            'avatar' => $member->avatar ?? self::DEFAULT_AVATAR,
        ];
    }
}
