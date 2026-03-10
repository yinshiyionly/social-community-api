<?php

namespace App\Services\Admin;

use App\Models\App\AppPostBase;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * 后台帖子查询服务。
 *
 * 职责：
 * 1. 提供帖子列表分页查询；
 * 2. 提供帖子详情查询；
 * 3. 统一封装帖子与作者、统计信息的关联加载。
 */
class PostService
{
    /**
     * 帖子审核状态：待审核。
     */
    private const AUDIT_STATUS_PENDING = 0;

    /**
     * 帖子审核状态：已通过。
     */
    private const AUDIT_STATUS_APPROVED = 1;

    /**
     * 帖子审核状态：已拒绝。
     */
    private const AUDIT_STATUS_REJECTED = 2;

    /**
     * 审核结果：成功。
     */
    public const AUDIT_RESULT_SUCCESS = 'success';

    /**
     * 审核结果：帖子不存在。
     */
    public const AUDIT_RESULT_NOT_FOUND = 'not_found';

    /**
     * 审核结果：帖子已审核。
     */
    public const AUDIT_RESULT_ALREADY_AUDITED = 'already_audited';

    /**
     * 后台列表查询字段。
     */
    private const LIST_COLUMNS = [
        'post_id',
        'member_id',
        'post_type',
        'title',
        'content',
        'cover',
        'image_show_style',
        'article_cover_style',
        'is_top',
        'sort_score',
        'visible',
        'status',
        'created_at',
    ];

    /**
     * 后台详情查询字段。
     */
    private const DETAIL_COLUMNS = [
        'post_id',
        'member_id',
        'post_type',
        'title',
        'content',
        'media_data',
        'cover',
        'image_show_style',
        'article_cover_style',
        'is_top',
        'sort_score',
        'visible',
        'status',
        'created_at',
        'updated_at',
    ];

    /**
     * 统计关联查询字段。
     */
    private const STAT_RELATION = 'stat:post_id,view_count,like_count,comment_count,share_count,collection_count';

    /**
     * 作者关联查询字段。
     */
    private const MEMBER_RELATION = 'member:member_id,nickname,avatar';

    /**
     * 获取帖子列表（分页）。
     *
     * 关键规则：
     * 1. 默认仅查询未软删数据（依赖模型 SoftDeletes 全局作用域）；
     * 2. 使用 is_top + sort_score + post_id 作为后台默认排序；
     * 3. 统计和作者信息通过关联预加载，避免列表 N+1 查询。
     *
     * @param array<string, mixed> $filters
     * @param int $pageNum
     * @param int $pageSize
     * @return LengthAwarePaginator
     */
    public function getList(array $filters, int $pageNum = 1, int $pageSize = 10): LengthAwarePaginator
    {
        $postId = $filters['postId'] ?? null;
        $memberId = $filters['memberId'] ?? null;
        $postType = $filters['postType'] ?? null;
        $status = $filters['status'] ?? null;
        $visible = $filters['visible'] ?? null;
        $isTop = $filters['isTop'] ?? null;
        $beginTime = $filters['beginTime'] ?? null;
        $endTime = $filters['endTime'] ?? null;

        $query = AppPostBase::query()
            ->select(self::LIST_COLUMNS)
            ->with([self::STAT_RELATION, self::MEMBER_RELATION]);

        if (!is_null($postId)) {
            $query->where('post_id', (int) $postId);
        }

        if (!is_null($memberId)) {
            $query->where('member_id', (int) $memberId);
        }

        if (!is_null($postType)) {
            $query->where('post_type', (int) $postType);
        }

        if (!is_null($status)) {
            $query->where('status', (int) $status);
        }

        if (!is_null($visible)) {
            $query->where('visible', (int) $visible);
        }

        if (!is_null($isTop)) {
            $query->where('is_top', (int) $isTop);
        }

        if (!empty($beginTime)) {
            $query->where('created_at', '>=', $beginTime);
        }

        if (!empty($endTime)) {
            $query->where('created_at', '<=', $endTime);
        }

        $query->orderByDesc('is_top')
            ->orderByDesc('sort_score')
            ->orderByDesc('post_id');

        return $query->paginate($pageSize, ['*'], 'pageNum', $pageNum);
    }

    /**
     * 获取帖子详情。
     *
     * @param int $postId 帖子ID
     * @return AppPostBase|null
     */
    public function getDetail(int $postId): ?AppPostBase
    {
        return AppPostBase::query()
            ->select(self::DETAIL_COLUMNS)
            ->with([self::STAT_RELATION, self::MEMBER_RELATION])
            ->where('post_id', $postId)
            ->first();
    }

    /**
     * 审核帖子（通过/拒绝）。
     *
     * 关键规则：
     * 1. 仅允许审核未软删帖子；
     * 2. 仅允许将待审核(0)流转为已通过(1)或已拒绝(2)；
     * 3. 当目标状态与当前状态一致时按幂等成功处理，避免重复点击产生误报。
     *
     * @param int $postId 帖子ID
     * @param int $status 目标状态：1=已通过，2=已拒绝
     * @return string 审核结果标识（success|not_found|already_audited）
     */
    public function audit(int $postId, int $status): string
    {
        $post = AppPostBase::query()
            ->select(['post_id', 'status'])
            ->where('post_id', $postId)
            ->first();

        if (!$post) {
            return self::AUDIT_RESULT_NOT_FOUND;
        }

        if (!in_array($status, [self::AUDIT_STATUS_APPROVED, self::AUDIT_STATUS_REJECTED], true)) {
            return self::AUDIT_RESULT_ALREADY_AUDITED;
        }

        if ((int) $post->status === $status) {
            return self::AUDIT_RESULT_SUCCESS;
        }

        // 仅待审核状态允许进入审核流，避免覆盖历史审核结果。
        if ((int) $post->status !== self::AUDIT_STATUS_PENDING) {
            return self::AUDIT_RESULT_ALREADY_AUDITED;
        }

        $post->status = $status;
        $post->save();

        return self::AUDIT_RESULT_SUCCESS;
    }
}
