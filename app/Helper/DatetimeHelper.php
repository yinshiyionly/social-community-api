<?php

namespace App\Helper;

use Carbon\Carbon;

class DatetimeHelper
{
    /**
     * 获取人类可读的时间差（中文格式）
     *
     * @param Carbon|string|null $targetTime 目标时间
     * @param Carbon|string|null $compareTime 比较时间，默认为当前时间
     * @return string 人类可读的时间差，如 "还有32天9小时26分" 或 "已过期3天"
     */
    public static function diffForHumansCN($targetTime, $compareTime = null): string
    {
        if (!$targetTime) {
            return '';
        }

        // 转换为 Carbon 实例
        $target = $targetTime instanceof Carbon ? $targetTime : Carbon::parse($targetTime);
        $compare = $compareTime ? ($compareTime instanceof Carbon ? $compareTime : Carbon::parse($compareTime)) : now();

        // 如果目标时间已过期（在比较时间之前）
        if ($target->lt($compare)) {
            $diff = $compare->diff($target);
            $parts = [];
            if ($diff->days > 0) {
                $parts[] = sprintf('%d天', $diff->days);
            }
            if ($diff->h > 0) {
                $parts[] = sprintf('%d小时', $diff->h);
            }
            if ($diff->i > 0) {
                $parts[] = sprintf('%d分', $diff->i);
            }
            return $parts ? '已过期' . implode('', $parts) : '已过期';
        }

        // 未过期，计算剩余时间
        $diff = $compare->diff($target);

        $parts = [];
        if ($diff->days > 0) {
            $parts[] = sprintf('%d天', $diff->days);
        }
        if ($diff->h > 0) {
            $parts[] = sprintf('%d小时', $diff->h);
        }
        if ($diff->i > 0) {
            $parts[] = sprintf('%d分', $diff->i);
        }

        return $parts ? '还有' . implode('', $parts) : '即将过期';
    }

    /**
     * 获取相对时间描述（中文）
     *
     * 如：刚刚、3分钟前、1小时前、昨天、2天前、2025-01-01
     *
     * @param \Carbon\Carbon|string|null $time
     * @return string
     */
    public static function relativeTime($time): string
    {
        if (!$time) {
            return '';
        }

        $carbon = $time instanceof Carbon ? $time : Carbon::parse($time);
        $now = Carbon::now();
        $diffInSeconds = $now->diffInSeconds($carbon, false);

        // 未来时间直接返回日期
        if ($diffInSeconds > 0) {
            return $carbon->format('Y-m-d');
        }

        $diffInSeconds = abs($diffInSeconds);
        $diffInMinutes = (int) floor($diffInSeconds / 60);
        $diffInHours = (int) floor($diffInMinutes / 60);
        $diffInDays = (int) floor($diffInHours / 24);

        if ($diffInSeconds < 60) {
            return '刚刚';
        }

        if ($diffInMinutes < 60) {
            return $diffInMinutes . '分钟前';
        }

        if ($diffInHours < 24) {
            return $diffInHours . '小时前';
        }

        if ($diffInDays === 1) {
            return '昨天';
        }

        if ($diffInDays <= 7) {
            return $diffInDays . '天前';
        }

        // 超过7天显示具体日期
        if ($carbon->year === $now->year) {
            return $carbon->format('m-d');
        }

        return $carbon->format('Y-m-d');
    }

}
