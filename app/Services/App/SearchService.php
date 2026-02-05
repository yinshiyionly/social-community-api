<?php

namespace App\Services\App;

use App\Models\App\AppCourseBase;
use App\Models\App\AppMemberBase;
use App\Models\App\AppMemberCourse;
use App\Models\App\AppMemberFollow;
use App\Models\App\AppPostBase;
use App\Http\Resources\App\MemberSearchResource;
use App\Http\Resources\App\PostSearchResource;
use App\Http\Resources\App\SearchAllCourseResource;
use App\Http\Resources\App\SearchAllUserResource;

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

    /**
     * 搜索全部（用户+课程混合分页）
     *
     * @param string $keyword 搜索关键词
     * @param int $page 页码
     * @param int $pageSize 每页数量
     * @param int|null $currentMemberId 当前登录用户ID
     * @return array
     */
    public function searchAll(string $keyword, int $page, int $pageSize, ?int $currentMemberId = null): array
    {
        // 分别查询用户和课程总数
        $userTotal = AppMemberBase::normal()
            ->where('nickname', 'ILIKE', '%' . $keyword . '%')
            ->count();

        $courseTotal = AppCourseBase::online()
            ->where('course_title', 'ILIKE', '%' . $keyword . '%')
            ->count();

        $total = $userTotal + $courseTotal;

        // 计算偏移量
        $offset = ($page - 1) * $pageSize;

        // 混合查询：先用户后课程
        $list = [];
        $remaining = $pageSize;

        // 如果偏移量在用户范围内
        if ($offset < $userTotal) {
            $userLimit = min($remaining, $userTotal - $offset);
            $users = $this->searchAllUsers($keyword, $offset, $userLimit, $currentMemberId);
            $list = array_merge($list, $users);
            $remaining -= count($users);
        }

        // 如果还需要课程数据
        if ($remaining > 0) {
            $courseOffset = max(0, $offset - $userTotal);
            $courses = $this->searchAllCourses($keyword, $courseOffset, $remaining, $currentMemberId);
            $list = array_merge($list, $courses);
        }

        $hasMore = ($offset + $pageSize) < $total;

        return [
            'list' => $list,
            'total' => $total,
            'hasMore' => $hasMore,
        ];
    }

    /**
     * 搜索全部 - 用户部分
     *
     * @param string $keyword
     * @param int $offset
     * @param int $limit
     * @param int|null $currentMemberId
     * @return array
     */
    private function searchAllUsers(string $keyword, int $offset, int $limit, ?int $currentMemberId): array
    {
        $users = AppMemberBase::normal()
            ->select(['member_id', 'nickname', 'avatar', 'fans_count'])
            ->where('nickname', 'ILIKE', '%' . $keyword . '%')
            ->orderByDesc('fans_count')
            ->orderByDesc('member_id')
            ->offset($offset)
            ->limit($limit)
            ->get();

        if ($users->isEmpty()) {
            return [];
        }

        // 批量查询关注状态
        $followedIds = [];
        if ($currentMemberId) {
            $userIds = $users->pluck('member_id')->toArray();
            $followedIds = AppMemberFollow::normal()
                ->byMember($currentMemberId)
                ->whereIn('follow_member_id', $userIds)
                ->pluck('follow_member_id')
                ->toArray();
        }

        $result = [];
        foreach ($users as $user) {
            $resource = new SearchAllUserResource($user);
            $resource->setIsFollowed(in_array($user->member_id, $followedIds));
            $result[] = [
                'type' => 'user',
                'data' => $resource->resolve(),
            ];
        }

        return $result;
    }

    /**
     * 搜索全部 - 课程部分
     *
     * @param string $keyword
     * @param int $offset
     * @param int $limit
     * @param int|null $currentMemberId
     * @return array
     */
    private function searchAllCourses(string $keyword, int $offset, int $limit, ?int $currentMemberId): array
    {
        $courses = AppCourseBase::online()
            ->select(['course_id', 'course_title', 'course_subtitle', 'current_price', 'original_price', 'cover_image', 'total_chapter'])
            ->where('course_title', 'ILIKE', '%' . $keyword . '%')
            ->orderByDesc('enroll_count')
            ->orderByDesc('course_id')
            ->offset($offset)
            ->limit($limit)
            ->get();

        if ($courses->isEmpty()) {
            return [];
        }

        // 批量查询学习状态
        $learningIds = [];
        if ($currentMemberId) {
            $courseIds = $courses->pluck('course_id')->toArray();
            $learningIds = AppMemberCourse::byMember($currentMemberId)
                ->notExpired()
                ->whereIn('course_id', $courseIds)
                ->pluck('course_id')
                ->toArray();
        }

        $result = [];
        foreach ($courses as $course) {
            $resource = new SearchAllCourseResource($course);
            $resource->setIsLearning(in_array($course->course_id, $learningIds));
            $result[] = [
                'type' => 'course',
                'data' => $resource->resolve(),
            ];
        }

        return $result;
    }
}
