<?php

namespace App\Services\App;

use App\Constant\MessageType;
use App\Helper\DatetimeHelper;
use App\Jobs\App\CreateInteractionMessageJob;
use App\Models\App\AppMemberBase;
use App\Models\App\AppMessageInteraction;
use App\Models\App\AppMessageSystem;
use App\Models\App\AppMessageUnreadCount;
use App\Models\App\AppMessageSystemUnread;
use App\Models\App\AppMemberFollow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 消息服务类
 */
class MessageService
{
    /**
     * 获取未读数统计
     *
     * @param int $memberId
     * @return array
     */
    public function getUnreadCount(int $memberId): array
    {
        $unreadCount = AppMessageUnreadCount::getOrCreate($memberId);

        $likeAndCollect = $unreadCount->getLikeAndCollectCount();
        $comment = $unreadCount->comment_count;
        $follow = $unreadCount->follow_count;
        $system = $unreadCount->system_count;
        $total = $likeAndCollect + $comment + $follow + $system;

        return [
            'total' => $total,
            'likeAndCollect' => $likeAndCollect,
            'comment' => $comment,
            'follow' => $follow,
            'system' => $system,
        ];
    }

    /**
     * 获取消息分类列表（固定4个分类 + 未读数 + 最新消息摘要）
     *
     * @param int $memberId
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function getMessageList(int $memberId, int $page = 1, int $pageSize = 10): array
    {
        $unreadCount = AppMessageUnreadCount::getOrCreate($memberId);

        // 小秘书官方账号ID
        $secretaryMemberId = 1;

        // 获取各分类最新一条消息
        $latestLikeCollect = AppMessageInteraction::byReceiver($memberId)
            ->likeAndCollect()
            ->with('sender:member_id,nickname,avatar')
            ->orderBy('message_id', 'desc')
            ->first();

        $latestComment = AppMessageInteraction::byReceiver($memberId)
            ->byType(MessageType::COMMENT)
            ->with('sender:member_id,nickname,avatar')
            ->orderBy('message_id', 'desc')
            ->first();

        $latestFollow = AppMessageInteraction::byReceiver($memberId)
            ->byType(MessageType::FOLLOW)
            ->with('sender:member_id,nickname,avatar')
            ->orderBy('message_id', 'desc')
            ->first();

        $latestSecretary = AppMessageSystem::forReceiver($memberId)
            ->bySender($secretaryMemberId)
            ->orderBy('message_id', 'desc')
            ->first();

        // 消息分类图标（可通过配置覆盖）
        $icons = config('app.message_icons', []);

        $categories = [
            [
                'id' => 1,
                'type' => '赞和收藏',
                'detail' => $latestLikeCollect ? $this->formatInteractionSummary($latestLikeCollect)['content'] : '',
                'time' => $latestLikeCollect && $latestLikeCollect->created_at
                    ? DatetimeHelper::relativeTime($latestLikeCollect->created_at) : '',
                'avatar' => $icons['likeAndCollect'] ?? '',
                'count' => $unreadCount->getLikeAndCollectCount(),
            ],
            [
                'id' => 2,
                'type' => '评论我的',
                'detail' => $latestComment ? $this->formatInteractionSummary($latestComment)['content'] : '',
                'time' => $latestComment && $latestComment->created_at
                    ? DatetimeHelper::relativeTime($latestComment->created_at) : '',
                'avatar' => $icons['comment'] ?? '',
                'count' => $unreadCount->comment_count,
            ],
            [
                'id' => 3,
                'type' => '关注我的',
                'detail' => $latestFollow ? $this->formatFollowSummary($latestFollow)['content'] : '',
                'time' => $latestFollow && $latestFollow->created_at
                    ? DatetimeHelper::relativeTime($latestFollow->created_at) : '',
                'avatar' => $icons['follow'] ?? '',
                'count' => $unreadCount->follow_count,
            ],
            [
                'id' => 4,
                'type' => '小秘书',
                'detail' => $latestSecretary ? $this->formatSystemSummary($latestSecretary)['content'] : '',
                'time' => $latestSecretary && $latestSecretary->created_at
                    ? DatetimeHelper::relativeTime($latestSecretary->created_at) : '',
                'avatar' => $icons['secretary'] ?? '',
                'count' => AppMessageSystemUnread::getUnreadCount($memberId, $secretaryMemberId),
            ],
        ];

        // 分页处理（固定4条数据）
        $total = count($categories);
        $offset = ($page - 1) * $pageSize;
        $list = array_slice($categories, $offset, $pageSize);

        return [
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
        ];
    }


    /**
     * 获取消息总列表（各分类最新一条 + 未读数）
     *
     * @param int $memberId
     * @return array
     */

        /**
         * 获取消息总列表（互动分类 + 官方账号会话列表）
         *
         * @param int $memberId
         * @return array
         */

            /**
             * 获取消息总列表（互动分类 + 官方账号统一列表，按最新消息时间倒序）
             *
             * @param int $memberId
             * @return array
             */
            public function getMessageOverview(int $memberId): array
            {
                $unreadCount = AppMessageUnreadCount::getOrCreate($memberId);
                $list = [];

                // 赞和收藏
                $latestLikeCollect = AppMessageInteraction::byReceiver($memberId)
                    ->likeAndCollect()
                    ->with('sender:member_id,nickname,avatar')
                    ->orderBy('message_id', 'desc')
                    ->first();

                $list[] = [
                    'itemType' => 'interaction',
                    'type' => 'likeAndCollect',
                    'title' => '赞和收藏',
                    'avatar' => null,
                    'isOfficial' => 0,
                    'officialLabel' => '',
                    'unreadCount' => $unreadCount->getLikeAndCollectCount(),
                    'latestContent' => $latestLikeCollect ? $this->formatInteractionSummary($latestLikeCollect)['content'] : '',
                    'latestTime' => $latestLikeCollect && $latestLikeCollect->created_at
                        ? $latestLikeCollect->created_at->format('Y-m-d H:i:s') : null,
                ];

                // 评论我的
                $latestComment = AppMessageInteraction::byReceiver($memberId)
                    ->byType(MessageType::COMMENT)
                    ->with('sender:member_id,nickname,avatar')
                    ->orderBy('message_id', 'desc')
                    ->first();

                $list[] = [
                    'itemType' => 'interaction',
                    'type' => 'comment',
                    'title' => '评论我的',
                    'avatar' => null,
                    'isOfficial' => 0,
                    'officialLabel' => '',
                    'unreadCount' => $unreadCount->comment_count,
                    'latestContent' => $latestComment ? $this->formatInteractionSummary($latestComment)['content'] : '',
                    'latestTime' => $latestComment && $latestComment->created_at
                        ? $latestComment->created_at->format('Y-m-d H:i:s') : null,
                ];

                // 关注我的
                $latestFollow = AppMessageInteraction::byReceiver($memberId)
                    ->byType(MessageType::FOLLOW)
                    ->with('sender:member_id,nickname,avatar')
                    ->orderBy('message_id', 'desc')
                    ->first();

                $list[] = [
                    'itemType' => 'interaction',
                    'type' => 'follow',
                    'title' => '关注我的',
                    'avatar' => null,
                    'isOfficial' => 0,
                    'officialLabel' => '',
                    'unreadCount' => $unreadCount->follow_count,
                    'latestContent' => $latestFollow ? $this->formatFollowSummary($latestFollow)['content'] : '',
                    'latestTime' => $latestFollow && $latestFollow->created_at
                        ? $latestFollow->created_at->format('Y-m-d H:i:s') : null,
                ];

                // 官方账号会话
                $officialItems = $this->getOfficialConversationItems($memberId);
                $list = array_merge($list, $officialItems);

                // 按最新消息时间倒序，无消息的排最后
                usort($list, function ($a, $b) {
                    $timeA = $a['latestTime'] ?? '0000-00-00 00:00:00';
                    $timeB = $b['latestTime'] ?? '0000-00-00 00:00:00';
                    return strcmp($timeB, $timeA);
                });

                return $list;
            }


        /**
         * 获取官方账号会话列表（每个官方账号的最新消息 + 未读数）
         *
         * @param int $memberId
         * @return array
         */

            /**
             * 获取官方账号会话列表项（统一格式）
             *
             * @param int $memberId
             * @return array
             */
            protected function getOfficialConversationItems(int $memberId): array
            {
                // 查询给当前用户发过系统消息的所有 sender_id（去重）
                $senderIds = AppMessageSystem::forReceiver($memberId)
                    ->whereNotNull('sender_id')
                    ->select('sender_id')
                    ->distinct()
                    ->pluck('sender_id')
                    ->toArray();

                if (empty($senderIds)) {
                    return [];
                }

                // 批量获取官方账号信息
                $senders = AppMemberBase::whereIn('member_id', $senderIds)
                    ->select(['member_id', 'nickname', 'avatar', 'is_official', 'official_label'])
                    ->get()
                    ->keyBy('member_id');

                // 批量获取每个发送者的未读数
                $unreadMap = AppMessageSystemUnread::where('member_id', $memberId)
                    ->whereIn('sender_id', $senderIds)
                    ->pluck('unread_count', 'sender_id')
                    ->toArray();

                $items = [];
                foreach ($senderIds as $senderId) {
                    $latestMessage = AppMessageSystem::forReceiver($memberId)
                        ->bySender($senderId)
                        ->orderBy('message_id', 'desc')
                        ->first();

                    if (!$latestMessage) {
                        continue;
                    }

                    $sender = $senders->get($senderId);
                    if (!$sender) {
                        continue;
                    }

                    $summary = $this->formatSystemSummary($latestMessage);

                    $items[] = [
                        'itemType' => 'official',
                        'type' => 'system',
                        'senderId' => $senderId,
                        'title' => $sender->nickname,
                        'avatar' => $sender->avatar,
                        'isOfficial' => $sender->is_official,
                        'officialLabel' => $sender->official_label,
                        'unreadCount' => $unreadMap[$senderId] ?? 0,
                        'latestContent' => $summary['content'],
                        'latestTime' => $summary['time'],
                    ];
                }

                return $items;
            }



    /**
     * 格式化互动消息摘要
     *
     * @param AppMessageInteraction $message
     * @return array
     */
    protected function formatInteractionSummary(AppMessageInteraction $message): array
    {
        $sender = $message->sender;

        return [
            'content' => $sender ? ($sender->nickname . MessageType::getTypeName($message->message_type) . '了您') : '',
            'time' => $message->created_at ? $message->created_at->format('Y-m-d H:i:s') : null,
        ];
    }

    /**
     * 格式化关注消息摘要
     *
     * @param AppMessageInteraction $message
     * @return array
     */
    protected function formatFollowSummary(AppMessageInteraction $message): array
    {
        $sender = $message->sender;

        return [
            'content' => $sender ? ($sender->nickname . '关注了您') : '',
            'time' => $message->created_at ? $message->created_at->format('Y-m-d H:i:s') : null,
        ];
    }

    /**
     * 格式化系统消息摘要
     *
     * @param AppMessageSystem $message
     * @return array
     */
    protected function formatSystemSummary(AppMessageSystem $message): array
    {
        $content = strip_tags($message->content);
        if (mb_strlen($content) > 50) {
            $content = mb_substr($content, 0, 50) . '...';
        }

        return [
            'content' => $message->title . '：' . $content,
            'time' => $message->created_at ? $message->created_at->format('Y-m-d H:i:s') : null,
        ];
    }


    /**
     * 获取赞和收藏消息列表
     *
     * @param int $memberId
     * @param string|null $cursor
     * @param int $pageSize
     * @return \Illuminate\Pagination\CursorPaginator
     */
    public function getLikeAndCollectMessages(int $memberId, int $pageNum, int $pageSize)
    {
        $query = AppMessageInteraction::byReceiver($memberId)
            ->likeAndCollect()
            ->with(['sender', 'post'])
            ->orderBy('message_id', 'desc');

        return $query->paginate($pageSize, ['*'], 'page', $pageNum);
    }

    /**
     * 获取评论消息列表
     *
     * @param int $memberId
     * @param string|null $cursor
     * @param int $pageSize
     * @return \Illuminate\Pagination\CursorPaginator
     */
    public function getCommentMessages(int $memberId, int $pageNum, int $pageSize)
    {
        $query = AppMessageInteraction::byReceiver($memberId)
            ->byType(MessageType::COMMENT)
            ->with(['sender', 'post'])
            ->orderBy('message_id', 'desc');

        return $query->paginate($pageSize, ['*'], 'page', $pageNum);
    }

    /**
     * 获取关注消息列表
     *
     * @param int $memberId
     * @param string|null $cursor
     * @param int $pageSize
     * @return \Illuminate\Pagination\CursorPaginator
     */
    public function getFollowMessages(int $memberId, int $pageNum, int $pageSize)
    {
        $query = AppMessageInteraction::byReceiver($memberId)
            ->byType(MessageType::FOLLOW)
            ->with('sender')
            ->orderBy('message_id', 'desc');

        return $query->paginate($pageSize, ['*'], 'page', $pageNum);
    }

    /**
     * 检查关注状态
     *
     * @param int $memberId 当前用户ID
     * @param array $targetMemberIds 目标用户ID列表
     * @return array 已关注的用户ID列表
     */
    public function getFollowedMemberIds(int $memberId, array $targetMemberIds): array
    {
        if (empty($targetMemberIds)) {
            return [];
        }

        return AppMemberFollow::where('member_id', $memberId)
            ->whereIn('follow_member_id', $targetMemberIds)
            ->pluck('follow_member_id')
            ->toArray();
    }

    /**
     * 获取系统消息列表
     *
     * @param int $memberId
     * @param string|null $cursor
     * @param int $pageSize
     * @return \Illuminate\Pagination\CursorPaginator
     */
    public function getSystemMessages(int $memberId, int $pageNum, int $pageSize)
    {
        $query = AppMessageSystem::forReceiver($memberId)
            ->orderBy('message_id', 'desc');

        return $query->paginate($pageSize, ['*'], 'page', $pageNum);
    }

    /**
     * 获取指定官方账号的消息列表
     *
     * @param int $memberId
     * @param int $senderId 官方账号的会员ID
     * @param string|null $cursor
     * @param int $pageSize
     * @return \Illuminate\Pagination\CursorPaginator
     */
    public function getSystemMessagesBySender(int $memberId, int $senderId, int $pageNum, int $pageSize)
    {
        $query = AppMessageSystem::forReceiver($memberId)
            ->bySender($senderId)
            ->orderBy('message_id', 'desc');

        return $query->paginate($pageSize, ['*'], 'page', $pageNum);
    }

    /**
     * 标记指定官方账号的消息为已读
     *
     * @param int $memberId
     * @param int $senderId
     * @return void
     */
    public function markSystemReadBySender(int $memberId, int $senderId): void
    {
        DB::beginTransaction();

        try {
            AppMessageSystem::byReceiver($memberId)
                ->bySender($senderId)
                ->unread()
                ->update(['is_read' => AppMessageSystem::READ_YES]);

            AppMessageUnreadCount::clearSystem($memberId, $senderId);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('标记官方账号消息已读失败', [
                'member_id' => $memberId,
                'sender_id' => $senderId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * 获取系统消息详情
     *
     * @param int $memberId
     * @param int $messageId
     * @return AppMessageSystem|null
     */
    public function getSystemDetail(int $memberId, int $messageId)
    {
        $message = AppMessageSystem::forReceiver($memberId)
            ->where('message_id', $messageId)
            ->first();

        if ($message && $message->is_read === AppMessageSystem::READ_NO) {
            // 标记为已读
            $message->update(['is_read' => AppMessageSystem::READ_YES]);
        }

        return $message;
    }

    /**
     * 标记消息为已读
     *
     * @param int $memberId
     * @param string $type 消息类型：likeAndCollect/comment/follow/system/all
     * @return void
     */
    public function markAsRead(int $memberId, string $type): void
    {
        DB::beginTransaction();

        try {
            switch ($type) {
                case 'likeAndCollect':
                    // 标记点赞和收藏消息为已读
                    AppMessageInteraction::byReceiver($memberId)
                        ->likeAndCollect()
                        ->unread()
                        ->update(['is_read' => AppMessageInteraction::READ_YES]);
                    // 清空未读数
                    AppMessageUnreadCount::clearLike($memberId);
                    AppMessageUnreadCount::clearCollect($memberId);
                    break;

                case 'comment':
                    AppMessageInteraction::byReceiver($memberId)
                        ->byType(MessageType::COMMENT)
                        ->unread()
                        ->update(['is_read' => AppMessageInteraction::READ_YES]);
                    AppMessageUnreadCount::clearComment($memberId);
                    break;

                case 'follow':
                    AppMessageInteraction::byReceiver($memberId)
                        ->byType(MessageType::FOLLOW)
                        ->unread()
                        ->update(['is_read' => AppMessageInteraction::READ_YES]);
                    AppMessageUnreadCount::clearFollow($memberId);
                    break;

                case 'system':
                    AppMessageSystem::byReceiver($memberId)
                        ->unread()
                        ->update(['is_read' => AppMessageSystem::READ_YES]);
                    AppMessageUnreadCount::clearSystem($memberId);
                    break;

                case 'all':
                    // 标记所有消息为已读
                    AppMessageInteraction::byReceiver($memberId)
                        ->unread()
                        ->update(['is_read' => AppMessageInteraction::READ_YES]);
                    AppMessageSystem::byReceiver($memberId)
                        ->unread()
                        ->update(['is_read' => AppMessageSystem::READ_YES]);
                    // 清空所有未读数
                    AppMessageUnreadCount::where('member_id', $memberId)->update([
                        'like_count' => 0,
                        'collect_count' => 0,
                        'comment_count' => 0,
                        'follow_count' => 0,
                        'system_count' => 0,
                    ]);
                    AppMessageSystemUnread::clearAll($memberId);
                    break;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('job')->error('标记消息已读失败', [
                'member_id' => $memberId,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * 创建互动消息（异步队列）
     *
     * @param array $data 消息数据
     * @return void
     */
    public static function createInteractionMessage(array $data): void
    {
        // 不给自己发消息
        if ($data['sender_id'] === $data['receiver_id']) {
            return;
        }

        // 分发到异步队列
        CreateInteractionMessageJob::dispatch($data);
    }

    /**
     * 创建点赞消息（异步队列）
     *
     * @param int $senderId 发送者ID
     * @param int $receiverId 接收者ID
     * @param int $targetId 目标ID
     * @param int $targetType 目标类型
     * @param string|null $contentSummary 内容摘要
     * @param string|null $coverUrl 封面URL
     * @return void
     */
    public static function createLikeMessage(
        int $senderId,
        int $receiverId,
        int $targetId,
        int $targetType,
        ?string $contentSummary = null,
        ?string $coverUrl = null
    ): void {
        self::createInteractionMessage([
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'message_type' => MessageType::LIKE,
            'target_id' => $targetId,
            'target_type' => $targetType,
            'content_summary' => $contentSummary,
            'cover_url' => $coverUrl,
        ]);
    }

    /**
     * 创建收藏消息（异步队列）
     *
     * @param int $senderId 发送者ID
     * @param int $receiverId 接收者ID
     * @param int $targetId 目标ID
     * @param int $targetType 目标类型
     * @param string|null $contentSummary 内容摘要
     * @param string|null $coverUrl 封面URL
     * @return void
     */
    public static function createCollectMessage(
        int $senderId,
        int $receiverId,
        int $targetId,
        int $targetType,
        ?string $contentSummary = null,
        ?string $coverUrl = null
    ): void {
        self::createInteractionMessage([
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'message_type' => MessageType::COLLECT,
            'target_id' => $targetId,
            'target_type' => $targetType,
            'content_summary' => $contentSummary,
            'cover_url' => $coverUrl,
        ]);
    }

    /**
     * 创建评论消息（异步队列）
     *
     * @param int $senderId 发送者ID
     * @param int $receiverId 接收者ID
     * @param int $targetId 目标ID
     * @param int $targetType 目标类型
     * @param string|null $contentSummary 内容摘要
     * @param string|null $coverUrl 封面URL
     * @return void
     */
    public static function createCommentMessage(
        int $senderId,
        int $receiverId,
        int $targetId,
        int $targetType,
        ?string $contentSummary = null,
        ?string $coverUrl = null
    ): void {
        self::createInteractionMessage([
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'message_type' => MessageType::COMMENT,
            'target_id' => $targetId,
            'target_type' => $targetType,
            'content_summary' => $contentSummary,
            'cover_url' => $coverUrl,
        ]);
    }

    /**
     * 创建关注消息（异步队列）
     *
     * @param int $senderId 发送者ID
     * @param int $receiverId 接收者ID
     * @return void
     */
    public static function createFollowMessage(int $senderId, int $receiverId): void
    {
        self::createInteractionMessage([
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'message_type' => MessageType::FOLLOW,
            'target_id' => null,
            'target_type' => null,
            'content_summary' => null,
            'cover_url' => null,
        ]);
    }

    /**
     * 创建系统消息（官方账号发送给用户）
     *
     * @param int $senderId 发送者会员ID（官方账号）
     * @param int $receiverId 接收者会员ID（NULL表示全员广播）
     * @param string $title 消息标题
     * @param string $content 消息内容
     * @param array $options 可选参数 [cover_url, link_type, link_url]
     * @return AppMessageSystem
     */
    public static function createSystemMessage(
        int $senderId,
        ?int $receiverId,
        string $title,
        string $content,
        array $options = []
    ): AppMessageSystem {
        $message = AppMessageSystem::create([
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'title' => $title,
            'content' => $content,
            'cover_url' => $options['cover_url'] ?? null,
            'link_type' => $options['link_type'] ?? null,
            'link_url' => $options['link_url'] ?? null,
            'is_read' => AppMessageSystem::READ_NO,
        ]);

        // 更新未读数
        if ($receiverId) {
            AppMessageUnreadCount::incrementSystem($receiverId, 1, $senderId);
        }

        return $message;
    }

}
