<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 后台帖子详情资源。
 */
class PostResource extends JsonResource
{
    /**
     * 帖子类型文案映射。
     */
    private const POST_TYPE_TEXT = [
        1 => '图文',
        2 => '视频',
        3 => '文章',
    ];

    /**
     * 可见性文案映射。
     */
    private const VISIBLE_TEXT = [
        0 => '私密',
        1 => '公开',
    ];

    /**
     * 状态文案映射（按迁移定义）。
     */
    private const STATUS_TEXT = [
        0 => '待审核',
        1 => '已通过',
        2 => '已拒绝',
    ];

    /**
     * 输出后台帖子详情。
     *
     * 字段约定：
     * - content/mediaData/cover 用于详情页完整展示；
     * - 统计字段缺失时统一返回 0，避免前端判空分支。
     *
     * @param \Illuminate\Http\Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $postType = (int) $this->post_type;
        $visible = (int) $this->visible;
        $status = (int) $this->status;

        $member = $this->member;
        $stat = $this->stat;

        return [
            'postId' => (int) $this->post_id,
            'memberId' => (int) $this->member_id,
            'memberNickname' => $member ? (string) ($member->nickname ?? '') : '',
            'memberAvatar' => $member ? (string) ($member->avatar ?? '') : '',
            'postType' => $postType,
            'postTypeText' => self::POST_TYPE_TEXT[$postType] ?? '未知',
            'title' => (string) ($this->title ?? ''),
            'content' => (string) ($this->content ?? ''),
            'mediaData' => $this->media_data ?? [],
            'cover' => $this->cover ?? [],
            'imageShowStyle' => (int) $this->image_show_style,
            'articleCoverStyle' => (int) $this->article_cover_style,
            'isTop' => (int) $this->is_top,
            'sortScore' => (float) $this->sort_score,
            'visible' => $visible,
            'visibleText' => self::VISIBLE_TEXT[$visible] ?? '未知',
            'status' => $status,
            'statusText' => self::STATUS_TEXT[$status] ?? '未知',
            'viewCount' => $stat ? (int) $stat->view_count : 0,
            'likeCount' => $stat ? (int) $stat->like_count : 0,
            'commentCount' => $stat ? (int) $stat->comment_count : 0,
            'shareCount' => $stat ? (int) $stat->share_count : 0,
            'collectionCount' => $stat ? (int) $stat->collection_count : 0,
            'createdAt' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'updatedAt' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
