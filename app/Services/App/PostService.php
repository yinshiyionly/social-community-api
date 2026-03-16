<?php

namespace App\Services\App;

use App\Constant\MessageType;
use App\Jobs\App\CleanupDeletedPostRelationsJob;
use App\Jobs\App\FillPostMediaInfoJob;
use App\Models\App\AppMemberBase;
use App\Models\App\AppPostBase;
use App\Models\App\AppPostCollection;
use App\Models\App\AppPostLike;
use App\Models\App\AppTopicPostRelation;
use App\Models\App\AppTopicStat;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * App 端帖子服务。
 *
 * 职责：
 * 1. 提供帖子发布、查询、删除能力；
 * 2. 提供帖子点赞/收藏交互能力；
 * 3. 处理帖子与话题、统计、异步任务等副作用。
 */
class PostService
{
    /**
     * 发表帖子
     *
     * @param int $memberId 会员ID
     * @param array $data 帖子数据
     * @return int 帖子ID
     * @throws \Exception
     */
    public function createPost(int $memberId, array $data): int
    {
        try {
            DB::beginTransaction();

            $insert = [
                'member_id' => $memberId,
                'post_type' => $data['post_type'],
                'title' => $data['title'],
                'content' => $data['content'],
                'media_data' => $data['media_data'],
                'cover' => $data['cover'],
                'image_show_style' => $data['image_show_style'] ?? AppPostBase::IMAGE_SHOW_STYLE_LARGE,
                'article_cover_style' => $data['article_cover_style'] ?? AppPostBase::ARTICLE_COVER_STYLE_SINGLE,
                'visible' => $data['visible'],
                'status' => AppPostBase::STATUS_PENDING,
            ];
            $post = AppPostBase::query()->create($insert);

            // 处理话题关联
            if (!empty($data['topics'])) {
                $this->attachTopics($post->post_id, $memberId, $data['topics']);
            }

            DB::commit();

            // 增加用户创作数
            AppMemberBase::where('member_id', $memberId)->increment('creation_count');

            // 派发异步任务填充媒体信息
            FillPostMediaInfoJob::dispatch($post->post_id, (int)$data['post_type']);

            return $post->post_id;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('保存入库失败-' . $e->getMessage());
        }
    }

    /**
     * 更新帖子（仅作者可更新，按类型全量覆盖）。
     *
     * 关键规则：
     * 1. 使用 post_id + post_type 精确定位，避免跨类型误更新；
     * 2. 仅作者允许更新，非作者返回 forbidden；
     * 3. 在事务内加行锁更新正文、媒体、可见性等字段，不修改 status/member_id/post_type；
     * 4. 话题关系按“全量替换”同步，并回收被移除话题的 post_count；
     * 5. 提交后派发媒体补全任务，保持与发布链路一致。
     *
     * @param int $memberId 当前登录用户ID
     * @param int $postId 帖子ID
     * @param int $postType 帖子类型
     * @param array<string, mixed> $data 更新数据（全量）
     * @return array{success:bool,message:string,updated:bool}
     */
    public function updatePostByOwner(int $memberId, int $postId, int $postType, array $data): array
    {
        $post = AppPostBase::query()
            ->select(['post_id', 'member_id'])
            ->where('post_id', $postId)
            ->where('post_type', $postType)
            ->first();

        if (!$post) {
            return [
                'success' => false,
                'message' => 'not_found',
                'updated' => false,
            ];
        }

        if ((int)$post->member_id !== $memberId) {
            return [
                'success' => false,
                'message' => 'forbidden',
                'updated' => false,
            ];
        }

        $result = [
            'success' => true,
            'message' => 'updated',
            'updated' => true,
        ];

        DB::transaction(function () use ($memberId, $postId, $postType, $data, &$result): void {
            $lockedPost = AppPostBase::query()
                ->select([
                    'post_id',
                    'member_id',
                    'title',
                    'content',
                    'media_data',
                    'cover',
                    'image_show_style',
                    'article_cover_style',
                    'visible',
                ])
                ->where('post_id', $postId)
                ->where('post_type', $postType)
                ->lockForUpdate()
                ->first();

            if (!$lockedPost) {
                $result = [
                    'success' => false,
                    'message' => 'not_found',
                    'updated' => false,
                ];
                return;
            }

            // 二次校验作者身份，避免并发场景下误更新非本人帖子。
            if ((int)$lockedPost->member_id !== $memberId) {
                $result = [
                    'success' => false,
                    'message' => 'forbidden',
                    'updated' => false,
                ];
                return;
            }

            $lockedPost->title = (string)($data['title'] ?? '');
            $lockedPost->content = (string)($data['content'] ?? '');
            $lockedPost->media_data = is_array($data['media_data'] ?? null) ? $data['media_data'] : [];
            $lockedPost->cover = is_array($data['cover'] ?? null) ? $data['cover'] : [];
            $lockedPost->image_show_style = (int)($data['image_show_style'] ?? AppPostBase::IMAGE_SHOW_STYLE_LARGE);
            $lockedPost->article_cover_style = (int)($data['article_cover_style'] ?? AppPostBase::ARTICLE_COVER_STYLE_SINGLE);
            $lockedPost->visible = (int)($data['visible'] ?? AppPostBase::VISIBLE_PUBLIC);
            $lockedPost->save();

            $topics = $data['topics'] ?? [];
            $this->syncPostTopics($postId, $memberId, is_array($topics) ? $topics : []);
        });

        if (!$result['success']) {
            return $result;
        }

        // 与发布链路保持一致：更新后重新补齐媒体元信息（时长、尺寸等衍生字段）。
        FillPostMediaInfoJob::dispatch($postId, $postType);

        return $result;
    }

    /**
     * 关联帖子与话题
     *
     * @param int $postId 帖子ID
     * @param int $memberId 会员ID
     * @param array $topics 话题数组 [['id' => 1, 'name' => '话题名'], ...]
     * @return void
     */
    protected function attachTopics(int $postId, int $memberId, array $topics): void
    {
        $now = now();

        foreach ($topics as $topic) {
            $topicId = $topic['id'];

            // 创建关联记录
            AppTopicPostRelation::create([
                'topic_id' => $topicId,
                'post_id' => $postId,
                'member_id' => $memberId,
                'is_featured' => AppTopicPostRelation::IS_FEATURED_NO,
                'created_at' => $now,
            ]);

            // 更新话题统计：帖子数+1
            AppTopicStat::query()
                ->where('topic_id', $topicId)
                ->increment('post_count');
        }
    }

    /**
     * 同步帖子话题关系（全量替换）。
     *
     * 同步顺序：
     * 1. 先删除被移除话题并回收统计；
     * 2. 再新增缺失话题并补增统计；
     * 3. 保留未变化话题，避免无意义写库。
     *
     * @param int $postId 帖子ID
     * @param int $memberId 发帖人ID
     * @param array<int, array<string, mixed>> $topics 目标话题列表
     * @return void
     */
    protected function syncPostTopics(int $postId, int $memberId, array $topics): void
    {
        $targetTopicIds = $this->extractTopicIds($topics);
        $currentTopicIds = AppTopicPostRelation::query()
            ->where('post_id', $postId)
            ->pluck('topic_id')
            ->map(static function ($topicId): int {
                return (int)$topicId;
            })
            ->toArray();

        $toRemoveTopicIds = array_values(array_diff($currentTopicIds, $targetTopicIds));
        $toAddTopicIds = array_values(array_diff($targetTopicIds, $currentTopicIds));

        if (!empty($toRemoveTopicIds)) {
            AppTopicPostRelation::query()
                ->where('post_id', $postId)
                ->whereIn('topic_id', $toRemoveTopicIds)
                ->delete();

            $this->decrementTopicPostCount($toRemoveTopicIds);
        }

        if (!empty($toAddTopicIds)) {
            $topicRows = array_map(static function (int $topicId): array {
                return ['id' => $topicId];
            }, $toAddTopicIds);

            $this->attachTopics($postId, $memberId, $topicRows);
        }
    }

    /**
     * 从话题请求数据提取唯一 topic_id 列表。
     *
     * @param array<int, array<string, mixed>> $topics
     * @return array<int, int>
     */
    protected function extractTopicIds(array $topics): array
    {
        $topicIdMap = [];
        foreach ($topics as $topic) {
            $topicId = isset($topic['id']) ? (int)$topic['id'] : 0;
            if ($topicId <= 0) {
                continue;
            }

            $topicIdMap[$topicId] = true;
        }

        return array_map(static function ($topicId): int {
            return (int)$topicId;
        }, array_keys($topicIdMap));
    }

    /**
     * 扣减话题帖子数（防止出现负数）。
     *
     * @param array<int, int> $topicIds
     * @return void
     */
    protected function decrementTopicPostCount(array $topicIds): void
    {
        foreach ($topicIds as $topicId) {
            DB::table('app_topic_stat')
                ->where('topic_id', (int)$topicId)
                ->update([
                    // 使用 GREATEST 防止并发扣减导致 post_count 为负数。
                    'post_count' => DB::raw('GREATEST(post_count - 1, 0)'),
                ]);
        }
    }

    /**
     * 帖子列表查询字段
     */
    private const POST_LIST_COLUMNS = [
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
        'created_at',
    ];

    /**
     * 帖子详情查询字段
     */
    private const POST_DETAIL_COLUMNS = [
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
        'created_at',
    ];

    /**
     * 会员关联查询字段
     */
    private const MEMBER_COLUMNS = 'member:member_id,nickname,avatar';

    /**
     * 统计关联
     */
    private const STAT_RELATION = 'stat:post_id,view_count,like_count,comment_count,share_count,collection_count';

    /**
     * 获取帖子列表（游标分页）
     *
     * @param string|null $cursor 游标
     * @param int $pageSize 每页数量
     * @return CursorPaginator
     */
    public function getPostList(?string $cursor = null, int $pageSize = 10): CursorPaginator
    {
        return AppPostBase::query()
            ->select(self::POST_LIST_COLUMNS)
            ->with([self::MEMBER_COLUMNS, self::STAT_RELATION])
            ->approved()
            ->visible()
            ->orderByDesc('is_top')
            ->orderByDesc('sort_score')
            ->orderByDesc('post_id')
            ->cursorPaginate($pageSize, ['*'], 'cursor', $cursor);
    }

    /**
     * 获取帖子列表（普通分页）
     *
     * @param int $page 页码
     * @param int $pageSize 每页数量
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getPostListPaginate(int $page = 1, int $pageSize = 10)
    {
        return AppPostBase::query()
            ->select(self::POST_LIST_COLUMNS)
            ->with([self::MEMBER_COLUMNS, self::STAT_RELATION])
            ->approved()
            ->visible()
            ->orderByDesc('is_top')
            ->orderByDesc('sort_score')
            ->orderByDesc('post_id')
            ->paginate($pageSize, ['*'], 'page', $page);
    }

    /**
     * 获取帖子详情
     *
     * @param int $postId 帖子ID
     * @return AppPostBase|null
     */
    public function getPostDetail(int $postId)
    {
        return AppPostBase::query()
            ->select(self::POST_DETAIL_COLUMNS)
            ->with([self::MEMBER_COLUMNS, self::STAT_RELATION])
            ->approved()
            ->visible()
            ->where('post_id', $postId)
            ->first();
    }

    /**
     * 获取指定类型的帖子详情
     *
     * @param int $postId 帖子ID
     * @param int $postType 帖子类型
     * @return AppPostBase|null
     */
    public function getPostDetailByType(int $postId, int $postType)
    {
        return AppPostBase::query()
            ->select(self::POST_DETAIL_COLUMNS)
            ->with([self::MEMBER_COLUMNS, self::STAT_RELATION])
            ->approved()
            ->visible()
            ->where('post_id', $postId)
            ->where('post_type', $postType)
            ->first();
    }

    /**
     * 增加帖子浏览量
     *
     * @param AppPostBase $post 帖子模型
     * @return bool
     */
    public function incrementViewCount(AppPostBase $post): bool
    {
        return $post->getOrCreateStat()->incrementViewCount();
    }

    /**
     * 删除帖子（仅作者可删除）。
     *
     * 关键规则：
     * 1. 使用 withTrashed 支持“已删除帖子”的幂等处理；
     * 2. 仅作者允许删除，非作者返回 forbidden；
     * 3. 未删除帖子在事务内执行软删 + 创作数安全回收（不低于 0）；
     * 4. 无论“刚删除”还是“已删除”，都尝试投递异步清理任务做补偿。
     *
     * 失败策略：
     * - 主链路删除失败抛异常，由上层统一返回通用错误；
     * - 队列投递失败仅记录日志，不影响删除接口成功返回。
     *
     * @param int $memberId 当前登录用户ID
     * @param int $postId 帖子ID
     * @param int|null $postType 帖子类型（可选，传入时参与精确匹配）
     * @return array{success:bool,message:string,deleted:bool}
     */
    public function deletePostByOwner(int $memberId, int $postId, ?int $postType = null): array
    {
        $postQuery = AppPostBase::query()
            ->withTrashed()
            ->select(['post_id', 'member_id', 'post_type', 'deleted_at'])
            ->where('post_id', $postId);

        if (!is_null($postType)) {
            $postQuery->where('post_type', $postType);
        }

        $post = $postQuery->first();
        if (!$post) {
            return [
                'success' => false,
                'message' => 'not_found',
                'deleted' => false,
            ];
        }

        if ((int)$post->member_id !== $memberId) {
            return [
                'success' => false,
                'message' => 'forbidden',
                'deleted' => false,
            ];
        }

        $resolvedPostType = (int)$post->post_type;
        $justDeleted = false;

        DB::transaction(function () use ($memberId, $postId, $postType, &$justDeleted): void {
            $lockQuery = AppPostBase::query()
                ->withTrashed()
                ->select(['post_id', 'member_id', 'deleted_at'])
                ->where('post_id', $postId)
                ->lockForUpdate();

            if (!is_null($postType)) {
                $lockQuery->where('post_type', $postType);
            }

            $lockedPost = $lockQuery->first();
            if (!$lockedPost) {
                return;
            }

            // 二次校验作者身份，避免并发场景下误删非本人帖子。
            if ((int)$lockedPost->member_id !== $memberId) {
                return;
            }

            // 已软删场景保持幂等，避免重复扣减创作数。
            if ($lockedPost->trashed()) {
                return;
            }

            $lockedPost->delete();
            AppMemberBase::query()
                ->where('member_id', $memberId)
                ->update([
                    // 使用数据库表达式防止并发场景出现负数。
                    'creation_count' => DB::raw('GREATEST(creation_count - 1, 0)'),
                ]);

            $justDeleted = true;
        });

        $this->dispatchCleanupDeletedPostRelationsJob($postId, $resolvedPostType, $memberId);

        return [
            'success' => true,
            'message' => $justDeleted ? 'deleted' : 'already_deleted',
            'deleted' => true,
        ];
    }

    /**
     * 投递“帖子关联清理”异步任务。
     *
     * 说明：
     * - 清理任务属于补偿逻辑，投递失败不应阻塞主删除链路；
     * - 失败只记录 job 日志，便于后续排障或人工补偿。
     *
     * @param int $postId
     * @param int $postType
     * @param int $memberId
     * @return void
     */
    protected function dispatchCleanupDeletedPostRelationsJob(int $postId, int $postType, int $memberId): void
    {
        try {
            CleanupDeletedPostRelationsJob::dispatch($postId, $postType, $memberId);
        } catch (\Throwable $e) {
            Log::channel('job')->error('投递帖子关联清理任务失败', [
                'post_id' => $postId,
                'post_type' => $postType,
                'operator_member_id' => $memberId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 收藏帖子
     *
     * @param int $memberId 会员ID
     * @param int $postId 帖子ID
     * @return array ['success' => bool, 'message' => string, 'is_collected' => bool]
     */
    public function collectPost(int $memberId, int $postId): array
    {
        // 检查帖子是否存在且可访问
        $post = AppPostBase::query()
            ->select(['post_id', 'member_id', 'content', 'cover'])
            ->approved()
            ->visible()
            ->where('post_id', $postId)
            ->first();

        if (!$post) {
            return [
                'success' => false,
                'message' => 'not_found',
                'is_collected' => false,
            ];
        }

        // 检查是否已收藏
        if (AppPostCollection::isCollected($memberId, $postId)) {
            return [
                'success' => true,
                'message' => 'already_collected',
                'is_collected' => true,
            ];
        }

        try {
            DB::beginTransaction();

            // 创建收藏记录
            AppPostCollection::create([
                'member_id' => $memberId,
                'post_id' => $postId,
            ]);

            // 增加帖子收藏数
            $post->getOrCreateStat()->incrementCollectionCount();

            // 增加用户收藏数
            AppMemberBase::where('member_id', $memberId)->increment('favorite_count');

            DB::commit();

            // 创建收藏消息（异步，不影响主流程）
            $coverUrl = isset($post->cover['url']) ? $post->cover['url'] : null;
            MessageService::createCollectMessage(
                $memberId,
                $post->member_id,
                $postId,
                MessageType::TARGET_POST,
                mb_substr($post->content, 0, 50),
                $coverUrl
            );

            return [
                'success' => true,
                'message' => 'collected',
                'is_collected' => true,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * 取消收藏帖子
     *
     * @param int $memberId 会员ID
     * @param int $postId 帖子ID
     * @return array ['success' => bool, 'message' => string, 'is_collected' => bool]
     */
    public function uncollectPost(int $memberId, int $postId): array
    {
        // 检查帖子是否存在
        $post = AppPostBase::query()
            ->select(['post_id'])
            ->where('post_id', $postId)
            ->first();

        if (!$post) {
            return [
                'success' => false,
                'message' => 'not_found',
                'is_collected' => false,
            ];
        }

        // 查找收藏记录
        $collection = AppPostCollection::where('member_id', $memberId)
            ->where('post_id', $postId)
            ->first();

        if (!$collection) {
            return [
                'success' => true,
                'message' => 'not_collected',
                'is_collected' => false,
            ];
        }

        try {
            DB::beginTransaction();

            // 删除收藏记录
            $collection->delete();

            // 减少帖子收藏数
            $post->getOrCreateStat()->decrementCollectionCount();

            // 减少用户收藏数
            AppMemberBase::where('member_id', $memberId)->decrement('favorite_count');

            DB::commit();

            return [
                'success' => true,
                'message' => 'uncollected',
                'is_collected' => false,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * 检查帖子是否已被收藏
     *
     * @param int $memberId 会员ID
     * @param int $postId 帖子ID
     * @return bool
     */
    public function isPostCollected(int $memberId, int $postId): bool
    {
        return AppPostCollection::isCollected($memberId, $postId);
    }

    /**
     * 批量检查帖子是否已被收藏
     *
     * @param int $memberId 会员ID
     * @param array $postIds 帖子ID数组
     * @return array 已收藏的帖子ID数组
     */
    public function getCollectedPostIds(int $memberId, array $postIds): array
    {
        return AppPostCollection::getCollectedPostIds($memberId, $postIds);
    }

    /**
     * 点赞帖子
     *
     * @param int $memberId 会员ID
     * @param int $postId 帖子ID
     * @return array ['success' => bool, 'message' => string, 'is_liked' => bool]
     */
    public function likePost(int $memberId, int $postId): array
    {
        // 检查帖子是否存在且可访问
        $post = AppPostBase::query()
            ->select(['post_id', 'member_id', 'content', 'cover'])
            ->approved()
            ->visible()
            ->where('post_id', $postId)
            ->first();

        if (!$post) {
            return [
                'success' => false,
                'message' => 'not_found',
                'is_liked' => false,
            ];
        }

        // 检查是否已点赞
        if (AppPostLike::isLiked($memberId, $postId)) {
            return [
                'success' => true,
                'message' => 'already_liked',
                'is_liked' => true,
            ];
        }

        try {
            DB::beginTransaction();

            // 创建点赞记录
            AppPostLike::create([
                'member_id' => $memberId,
                'post_id' => $postId,
            ]);

            // 增加帖子点赞数
            $post->getOrCreateStat()->incrementLikeCount();

            DB::commit();

            // 创建点赞消息（异步，不影响主流程）
            $coverUrl = isset($post->cover['url']) ? $post->cover['url'] : null;
            MessageService::createLikeMessage(
                $memberId,
                $post->member_id,
                $postId,
                MessageType::TARGET_POST,
                mb_substr($post->content, 0, 50),
                $coverUrl
            );

            return [
                'success' => true,
                'message' => 'liked',
                'is_liked' => true,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * 取消点赞帖子
     *
     * @param int $memberId 会员ID
     * @param int $postId 帖子ID
     * @return array ['success' => bool, 'message' => string, 'is_liked' => bool]
     */
    public function unlikePost(int $memberId, int $postId): array
    {
        // 检查帖子是否存在
        $post = AppPostBase::query()
            ->select(['post_id'])
            ->where('post_id', $postId)
            ->first();

        if (!$post) {
            return [
                'success' => false,
                'message' => 'not_found',
                'is_liked' => false,
            ];
        }

        // 查找点赞记录
        $like = AppPostLike::where('member_id', $memberId)
            ->where('post_id', $postId)
            ->first();

        if (!$like) {
            return [
                'success' => true,
                'message' => 'not_liked',
                'is_liked' => false,
            ];
        }

        try {
            DB::beginTransaction();

            // 删除点赞记录
            $like->delete();

            // 减少帖子点赞数
            $post->getOrCreateStat()->decrementLikeCount();

            DB::commit();

            return [
                'success' => true,
                'message' => 'unliked',
                'is_liked' => false,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * 检查帖子是否已被点赞
     *
     * @param int $memberId 会员ID
     * @param int $postId 帖子ID
     * @return bool
     */
    public function isPostLiked(int $memberId, int $postId): bool
    {
        return AppPostLike::isLiked($memberId, $postId);
    }

    /**
     * 批量检查帖子是否已被点赞
     *
     * @param int $memberId 会员ID
     * @param array $postIds 帖子ID数组
     * @return array 已点赞的帖子ID数组
     */
    public function getLikedPostIds(int $memberId, array $postIds): array
    {
        return AppPostLike::getLikedPostIds($memberId, $postIds);
    }

    /**
     * 视频流查询字段
     */
    private const VIDEO_FEED_COLUMNS = [
        'post_id',
        'member_id',
        'title',
        'content',
        'media_data',
        'cover',
        'created_at',
    ];

    /**
     * 获取视频流列表（游标分页）- 用于刷视频场景
     *
     * @param string|null $cursor 游标
     * @param int $pageSize 每页数量
     * @return CursorPaginator
     */
    public function getVideoFeed(?string $cursor = null, int $pageSize = 10): CursorPaginator
    {
        return AppPostBase::query()
            ->select(self::VIDEO_FEED_COLUMNS)
            ->with([self::MEMBER_COLUMNS, self::STAT_RELATION])
            ->byType(AppPostBase::POST_TYPE_VIDEO)
            ->approved()
            ->visible()
            ->orderByDesc('sort_score')
            ->orderByDesc('post_id')
            ->cursorPaginate($pageSize, ['*'], 'cursor', $cursor);
    }

    /**
     * 批量获取用户关注状态
     *
     * @param int $memberId 当前用户ID
     * @param array $targetMemberIds 目标用户ID数组
     * @return array 已关注的用户ID数组
     */
    public function getFollowedMemberIds(int $memberId, array $targetMemberIds): array
    {
        if (empty($targetMemberIds)) {
            return [];
        }

        return \App\Models\App\AppMemberFollow::query()
            ->byMember($memberId)
            ->whereIn('follow_member_id', $targetMemberIds)
            ->normal()
            ->pluck('follow_member_id')
            ->toArray();
    }
}
