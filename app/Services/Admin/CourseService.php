<?php

namespace App\Services\Admin;

use App\Models\App\AppCourseBase;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CourseService
{
    /**
     * 获取课程列表（分页）
     *
     * @param array $filters 筛选条件
     * @param int $pageNum 页码
     * @param int $pageSize 每页数量
     * @return LengthAwarePaginator
     */
    public function getList(array $filters, int $pageNum = 1, int $pageSize = 10): LengthAwarePaginator
    {
        $query = AppCourseBase::query()->with('category:category_id,category_name');

        // 课程标题搜索
        if (!empty($filters['courseTitle'])) {
            $query->where('course_title', 'like', '%' . $filters['courseTitle'] . '%');
        }

        // 课程编号搜索
        if (!empty($filters['courseNo'])) {
            $query->where('course_no', 'like', '%' . $filters['courseNo'] . '%');
        }

        // 分类筛选
        if (isset($filters['categoryId']) && $filters['categoryId'] !== '') {
            $query->where('category_id', $filters['categoryId']);
        }

        // 付费类型筛选
        if (isset($filters['payType']) && $filters['payType'] !== '') {
            $query->where('pay_type', $filters['payType']);
        }

        // 播放类型筛选
        if (isset($filters['playType']) && $filters['playType'] !== '') {
            $query->where('play_type', $filters['playType']);
        }

        // 状态筛选
        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        // 是否免费筛选
        if (isset($filters['isFree']) && $filters['isFree'] !== '') {
            $query->where('is_free', $filters['isFree']);
        }

        // 是否推荐筛选
        if (isset($filters['isRecommend']) && $filters['isRecommend'] !== '') {
            $query->where('is_recommend', $filters['isRecommend']);
        }

        // 时间范围筛选
        if (!empty($filters['beginTime'])) {
            $query->where('created_at', '>=', $filters['beginTime']);
        }
        if (!empty($filters['endTime'])) {
            $query->where('created_at', '<=', $filters['endTime']);
        }

        // 排序
        $query->orderByDesc('sort_order')->orderByDesc('course_id');

        return $query->paginate($pageSize, ['*'], 'pageNum', $pageNum);
    }

    /**
     * 获取课程详情
     *
     * @param int $courseId
     * @return AppCourseBase|null
     */
    public function getDetail(int $courseId): ?AppCourseBase
    {
        return AppCourseBase::query()
            ->with(['category:category_id,category_name', 'teacher:teacher_id,teacher_name,avatar,title'])
            ->where('course_id', $courseId)
            ->first();
    }

    /**
     * 创建课程
     *
     * @param array $data
     * @return AppCourseBase
     */
    public function create(array $data): AppCourseBase
    {
        return AppCourseBase::create([
            'course_no' => AppCourseBase::generateCourseNo(),
            'category_id' => $data['categoryId'],
            'course_title' => $data['courseTitle'],
            'course_subtitle' => $data['courseSubtitle'] ?? null,
            'pay_type' => $data['payType'],
            'play_type' => $data['playType'],
            'schedule_type' => $data['scheduleType'] ?? AppCourseBase::SCHEDULE_TYPE_FIXED,
            'cover_image' => $data['coverImage'] ?? null,
            'cover_video' => $data['coverVideo'] ?? null,
            'banner_images' => $data['bannerImages'] ?? [],
            'intro_video' => $data['introVideo'] ?? null,
            'brief' => $data['brief'] ?? null,
            'description' => $data['description'] ?? null,
            'suitable_crowd' => $data['suitableCrowd'] ?? null,
            'learn_goal' => $data['learnGoal'] ?? null,
            'teacher_id' => $data['teacherId'] ?? null,
            'assistant_ids' => $data['assistantIds'] ?? [],
            'original_price' => $data['originalPrice'] ?? 0,
            'current_price' => $data['currentPrice'] ?? 0,
            'point_price' => $data['pointPrice'] ?? 0,
            'is_free' => $data['isFree'] ?? 0,
            'valid_days' => $data['validDays'] ?? 0,
            'allow_download' => $data['allowDownload'] ?? 0,
            'allow_comment' => $data['allowComment'] ?? 1,
            'allow_share' => $data['allowShare'] ?? 1,
            'sort_order' => $data['sortOrder'] ?? 0,
            'is_recommend' => $data['isRecommend'] ?? 0,
            'is_hot' => $data['isHot'] ?? 0,
            'is_new' => $data['isNew'] ?? 0,
            'status' => AppCourseBase::STATUS_DRAFT,
        ]);
    }

    /**
     * 更新课程
     *
     * @param int $courseId
     * @param array $data
     * @return bool
     */
    public function update(int $courseId, array $data): bool
    {
        $course = AppCourseBase::query()->where('course_id', $courseId)->first();

        if (!$course) {
            return false;
        }

        $updateData = [];

        if (isset($data['categoryId'])) {
            $updateData['category_id'] = $data['categoryId'];
        }
        if (isset($data['courseTitle'])) {
            $updateData['course_title'] = $data['courseTitle'];
        }
        if (array_key_exists('courseSubtitle', $data)) {
            $updateData['course_subtitle'] = $data['courseSubtitle'];
        }
        if (isset($data['payType'])) {
            $updateData['pay_type'] = $data['payType'];
        }
        if (isset($data['playType'])) {
            $updateData['play_type'] = $data['playType'];
        }
        if (isset($data['scheduleType'])) {
            $updateData['schedule_type'] = $data['scheduleType'];
        }
        if (array_key_exists('coverImage', $data)) {
            $updateData['cover_image'] = $data['coverImage'];
        }
        if (array_key_exists('coverVideo', $data)) {
            $updateData['cover_video'] = $data['coverVideo'];
        }
        if (array_key_exists('bannerImages', $data)) {
            $updateData['banner_images'] = $data['bannerImages'] ?? [];
        }
        if (array_key_exists('introVideo', $data)) {
            $updateData['intro_video'] = $data['introVideo'];
        }
        if (array_key_exists('brief', $data)) {
            $updateData['brief'] = $data['brief'];
        }
        if (array_key_exists('description', $data)) {
            $updateData['description'] = $data['description'];
        }
        if (array_key_exists('suitableCrowd', $data)) {
            $updateData['suitable_crowd'] = $data['suitableCrowd'];
        }
        if (array_key_exists('learnGoal', $data)) {
            $updateData['learn_goal'] = $data['learnGoal'];
        }
        if (array_key_exists('teacherId', $data)) {
            $updateData['teacher_id'] = $data['teacherId'];
        }
        if (array_key_exists('assistantIds', $data)) {
            $updateData['assistant_ids'] = $data['assistantIds'] ?? [];
        }
        if (isset($data['originalPrice'])) {
            $updateData['original_price'] = $data['originalPrice'];
        }
        if (isset($data['currentPrice'])) {
            $updateData['current_price'] = $data['currentPrice'];
        }
        if (isset($data['pointPrice'])) {
            $updateData['point_price'] = $data['pointPrice'];
        }
        if (isset($data['isFree'])) {
            $updateData['is_free'] = $data['isFree'];
        }
        if (isset($data['validDays'])) {
            $updateData['valid_days'] = $data['validDays'];
        }
        if (isset($data['allowDownload'])) {
            $updateData['allow_download'] = $data['allowDownload'];
        }
        if (isset($data['allowComment'])) {
            $updateData['allow_comment'] = $data['allowComment'];
        }
        if (isset($data['allowShare'])) {
            $updateData['allow_share'] = $data['allowShare'];
        }
        if (isset($data['sortOrder'])) {
            $updateData['sort_order'] = $data['sortOrder'];
        }
        if (isset($data['isRecommend'])) {
            $updateData['is_recommend'] = $data['isRecommend'];
        }
        if (isset($data['isHot'])) {
            $updateData['is_hot'] = $data['isHot'];
        }
        if (isset($data['isNew'])) {
            $updateData['is_new'] = $data['isNew'];
        }

        return $course->update($updateData);
    }

    /**
     * 删除课程（支持批量，软删除）
     *
     * @param array $courseIds
     * @return int 删除数量
     */
    public function delete(array $courseIds): int
    {
        return AppCourseBase::query()
            ->whereIn('course_id', $courseIds)
            ->whereNull('deleted_at')
            ->update([
                'deleted_at' => now(),
                'deleted_by' => $this->getCurrentOperatorId(),
            ]);
    }

    /**
     * 修改课程状态
     *
     * @param int $courseId
     * @param int $status
     * @return bool
     */
    public function changeStatus(int $courseId, int $status): bool
    {
        $updateData = ['status' => $status];

        // 上架时记录上架时间
        if ($status === AppCourseBase::STATUS_ONLINE) {
            $updateData['publish_time'] = now();
        }

        return AppCourseBase::query()
                ->where('course_id', $courseId)
                ->update($updateData) > 0;
    }

    /**
     * 获取下拉选项列表（只返回上架状态的课程）
     *
     * @return Collection
     */
    public function getOptions(): Collection
    {
        return AppCourseBase::query()
            ->select(['course_id', 'course_no', 'course_title'])
            ->where('status', AppCourseBase::STATUS_ONLINE)
            ->orderByDesc('sort_order')
            ->orderByDesc('course_id')
            ->get();
    }

    /**
     * 检查课程是否存在
     *
     * @param int $courseId
     * @return bool
     */
    public function exists(int $courseId): bool
    {
        return AppCourseBase::query()
            ->where('course_id', $courseId)
            ->exists();
    }

    /**
     * 检查课程下是否有章节
     *
     * @param int $courseId
     * @return bool
     */
    public function hasChapters(int $courseId): bool
    {
        return DB::table('app_course_chapter')
            ->where('course_id', $courseId)
            ->whereNull('deleted_at')
            ->exists();
    }

    /**
     * 获取当前操作人ID
     *
     * @return int|null
     */
    protected function getCurrentOperatorId(): ?int
    {
        $request = request();

        // System 端用户（后台管理员）
        if ($request && $request->attributes->has('system_user_id')) {
            return (int)$request->attributes->get('system_user_id');
        }

        // Admin guard 登录用户
        if (Auth::guard('admin')->check()) {
            return (int)Auth::guard('admin')->id();
        }

        return null;
    }
}
