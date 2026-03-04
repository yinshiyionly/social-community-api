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
        $query = AppCourseBase::query()
            ->with('category:category_id,category_name')
            ->select([
                'course_id', 'course_no', 'category_id', 'course_title',
                'pay_type', 'play_type', 'schedule_type',
                'cover_image', 'item_image',
                'original_price', 'current_price', 'is_free',
                'status', 'publish_time', 'created_at',
            ]);

        // 课程标题搜索
        if (!empty($filters['courseTitle'])) {
            $query->where('course_title', 'like', '%' . $filters['courseTitle'] . '%');
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


        // 时间范围筛选
        if (!empty($filters['beginTime'])) {
            $query->where('created_at', '>=', $filters['beginTime']);
        }
        if (!empty($filters['endTime'])) {
            $query->where('created_at', '<=', $filters['endTime']);
        }

        // 排序
        $query->orderByDesc('course_id');

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
            ->with(['category:category_id,category_name'])
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
            'item_image' => $data['itemImage'] ?? null,
            'description' => $data['description'] ?? null,
            'remark' => $data['remark'] ?? null,
            'original_price' => $data['originalPrice'] ?? 0,
            'current_price' => $data['currentPrice'] ?? 0,
            'is_free' => $data['isFree'] ?? 0,
            'status' => $data['status'] ?? AppCourseBase::STATUS_DRAFT,
            'publish_time' => $data['publishTime'] ?? null,
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
        if (array_key_exists('itemImage', $data)) {
            $updateData['item_image'] = $data['itemImage'];
        }
        if (array_key_exists('description', $data)) {
            $updateData['description'] = $data['description'];
        }
        if (array_key_exists('remark', $data)) {
            $updateData['remark'] = $data['remark'];
        }
        if (isset($data['originalPrice'])) {
            $updateData['original_price'] = $data['originalPrice'];
        }
        if (isset($data['currentPrice'])) {
            $updateData['current_price'] = $data['currentPrice'];
        }
        if (isset($data['isFree'])) {
            $updateData['is_free'] = $data['isFree'];
        }
        if (isset($data['status'])) {
            $updateData['status'] = $data['status'];
        }
        if (array_key_exists('publishTime', $data)) {
            $updateData['publish_time'] = $data['publishTime'];
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
                // 'deleted_by' => $this->getCurrentOperatorId(),
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
     * 批量更新课程排序
     *
     * @param array $courseData [['courseId' => 1, 'courseSort' => 999], ...]
     * @return bool
     */
    public function batchUpdateSort(array $courseData): bool
    {
        return DB::transaction(function () use ($courseData) {
            foreach ($courseData as $item) {
                AppCourseBase::query()
                    ->where('course_id', $item['courseId'])
                    ->whereNull('deleted_at')
                    ->update(['sort_order' => $item['courseSort']]);
            }

            return true;
        });
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

    /**
     * 获取课表详情（课程 + 章节 + 章节内容）
     *
     * @param int $courseId
     * @return AppCourseBase|null
     */
    public function getSchedule(int $courseId): ?AppCourseBase
    {
        $course = AppCourseBase::query()
            ->select([
                'course_id', 'course_title', 'course_subtitle',
                'play_type', 'schedule_type', 'status',
                'total_chapter', 'total_duration', 'cover_image', 'teacher_id',
            ])
            ->where('course_id', $courseId)
            ->first();

        if (!$course) {
            return null;
        }

        // 根据播放类型决定预加载的章节内容关联
        $contentRelation = $this->getContentRelationByPlayType($course->play_type);

        // 加载章节
        $course->load([
            'chapters' => function ($query) {
                $query->select([
                    'chapter_id', 'course_id', 'chapter_no', 'chapter_title', 'chapter_subtitle',
                    'cover_image', 'is_free', 'is_preview',
                    'unlock_type', 'unlock_days', 'unlock_date', 'unlock_time',
                    'chapter_start_time', 'chapter_end_time',
                    'duration', 'has_homework', 'sort_order', 'status',
                ])
                ->orderBy('sort_order', 'asc')
                ->orderBy('chapter_id', 'asc');
            },
        ]);

        // 预加载章节内容（避免 N+1）
        if ($contentRelation && $course->chapters->isNotEmpty()) {
            $course->chapters->load($contentRelation);
        }

        return $course;
    }

    /**
     * 根据播放类型获取章节内容关联名
     *
     * @param int $playType
     * @return string|null
     */
    private function getContentRelationByPlayType(int $playType): ?string
    {
        $map = [
            AppCourseBase::PLAY_TYPE_VIDEO => 'videoContent',
            AppCourseBase::PLAY_TYPE_LIVE => 'liveContent',
            AppCourseBase::PLAY_TYPE_ARTICLE => 'articleContent',
            AppCourseBase::PLAY_TYPE_AUDIO => 'audioContent',
        ];

        return $map[$playType] ?? null;
    }

}
