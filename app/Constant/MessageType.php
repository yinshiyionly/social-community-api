<?php

namespace App\Constant;

/**
 * 消息类型常量
 */
class MessageType
{
    // ==================== 互动消息类型 ====================
    const LIKE = 1;      // 点赞
    const COLLECT = 2;   // 收藏
    const COMMENT = 3;   // 评论
    const FOLLOW = 4;    // 关注

    // ==================== 目标类型 ====================
    const TARGET_POST = 1;     // 帖子
    const TARGET_COMMENT = 2;  // 评论

    // ==================== 系统消息跳转类型 ====================
    const LINK_POST = 1;       // 帖子详情
    const LINK_ACTIVITY = 2;   // 活动页
    const LINK_EXTERNAL = 3;   // 外链
    const LINK_NONE = 4;       // 无跳转

    /**
     * 获取互动消息类型名称
     *
     * @param int $type
     * @return string
     */
    public static function getTypeName(int $type): string
    {
        $map = [
            self::LIKE => '点赞',
            self::COLLECT => '收藏',
            self::COMMENT => '评论',
            self::FOLLOW => '关注',
        ];

        return $map[$type] ?? '未知';
    }

    /**
     * 获取目标类型名称
     *
     * @param int $type
     * @return string
     */
    public static function getTargetTypeName(int $type): string
    {
        $map = [
            self::TARGET_POST => '帖子',
            self::TARGET_COMMENT => '评论',
        ];

        return $map[$type] ?? '未知';
    }

    /**
     * 获取跳转类型名称
     *
     * @param int $type
     * @return string
     */
    public static function getLinkTypeName(int $type): string
    {
        $map = [
            self::LINK_POST => '帖子详情',
            self::LINK_ACTIVITY => '活动页',
            self::LINK_EXTERNAL => '外链',
            self::LINK_NONE => '无跳转',
        ];

        return $map[$type] ?? '未知';
    }
}
