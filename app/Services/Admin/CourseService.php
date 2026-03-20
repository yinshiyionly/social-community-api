<?php

namespace App\Services\Admin;

use App\Models\App\AppCourseBase;
use App\Models\App\AppCourseChapter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * 后台课程管理服务。
 *
 * 职责：
 * 1. 提供课程列表/详情查询与创建、更新、删除能力；
 * 2. 统一课程状态流转、排序等后台管理操作；
 * 3. 聚合章节关系读取，向控制器输出稳定的课程业务数据。
 */
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
                'teacher_name', 'class_teacher_name', 'class_teacher_qr',
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
        $query->orderBy('sort_order')->orderByDesc('course_id');

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
            'teacher_name' => $data['teacherName'] ?? null,
            'class_teacher_name' => $data['classTeacherName'] ?? null,
            'class_teacher_qr' => $data['classTeacherQr'] ?? null,
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

        // 与创建接口保持一致：必填字段固定写入，避免更新规则漂移。
        $updateData = [
            'category_id' => $data['categoryId'],
            'course_title' => $data['courseTitle'],
            'pay_type' => $data['payType'],
            'play_type' => $data['playType'],
            'schedule_type' => $data['scheduleType'],
            'teacher_name' => $data['teacherName'],
            'class_teacher_name' => $data['classTeacherName'],
            'class_teacher_qr' => $data['classTeacherQr'],
            'cover_image' => $data['coverImage'],
            'item_image' => $data['itemImage'],
            'description' => $data['description'],
            'original_price' => $data['originalPrice'],
            'current_price' => $data['currentPrice'],
            'is_free' => $data['isFree'],
            'status' => $data['status'],
        ];

        // 可选字段仅在显式传入时更新，避免未提交字段被置空。
        if (array_key_exists('courseSubtitle', $data)) {
            $updateData['course_subtitle'] = $data['courseSubtitle'];
        }
        if (array_key_exists('remark', $data)) {
            $updateData['remark'] = $data['remark'];
        }
        if (array_key_exists('publishTime', $data)) {
            $updateData['publish_time'] = $data['publishTime'];
        }

        return $course->update($updateData);
    }

    /**
     * 复制录播课程（课程主信息 + 章节 + 章节视频内容）。
     *
     * 关键规则：
     * 1. 仅允许复制录播课（play_type=录播）；
     * 2. 新课程及新章节统一重置为草稿，课程 publish_time 清空；
     * 3. 章节按源课程顺序重建 chapter_no/sort_order，避免排序错乱。
     *
     * 失败策略：
     * - 任一章节缺失视频内容时中断并回滚，避免生成不可用课表。
     *
     * @param int $courseId 源课程ID
     * @return AppCourseBase 新课程
     */
    public function copyVideoCourse(int $courseId): AppCourseBase
    {
        return DB::transaction(function () use ($courseId) {
            $sourceCourse = AppCourseBase::query()
                ->where('course_id', $courseId)
                ->first();

            if (!$sourceCourse) {
                throw new \InvalidArgumentException('课程不存在');
            }

            if ((int)$sourceCourse->play_type !== AppCourseBase::PLAY_TYPE_VIDEO) {
                throw new \InvalidArgumentException('仅支持录播课复制');
            }

            $targetCourse = $sourceCourse->replicate();
            $targetCourse->course_no = AppCourseBase::generateCourseNo();
            $targetCourse->course_title = $this->appendCopySuffix((string)$sourceCourse->course_title, 200);
            // 复制出的课程默认回到草稿，避免误上架已发布内容。
            $targetCourse->status = AppCourseBase::STATUS_DRAFT;
            $targetCourse->publish_time = null;
            $targetCourse->total_chapter = 0;
            $targetCourse->total_duration = 0;
            $targetCourse->enroll_count = 0;
            $targetCourse->view_count = 0;
            $targetCourse->complete_count = 0;
            $targetCourse->comment_count = 0;
            $targetCourse->avg_rating = 5.0;
            $targetCourse->created_by = null;
            $targetCourse->updated_by = null;
            $targetCourse->deleted_at = null;
            $targetCourse->deleted_by = null;
            $targetCourse->save();

            $sourceChapters = AppCourseChapter::query()
                ->with('videoContent')
                ->where('course_id', $sourceCourse->course_id)
                ->orderBy('sort_order', 'asc')
                ->orderBy('chapter_no', 'asc')
                ->orderBy('chapter_id', 'asc')
                ->get();

            $copiedChapterCount = 0;
            $copiedTotalDuration = 0;

            foreach ($sourceChapters as $index => $sourceChapter) {
                $sourceVideoContent = $sourceChapter->videoContent;

                // 录播章节必须有视频内容，缺失时直接终止，避免复制后章节不可播放。
                if (!$sourceVideoContent) {
                    throw new \InvalidArgumentException('课程章节缺失视频内容，无法复制');
                }

                $targetChapter = $sourceChapter->replicate();
                $targetChapter->course_id = $targetCourse->course_id;
                $targetChapter->chapter_no = $index + 1;
                $targetChapter->sort_order = $index + 1;
                $targetChapter->chapter_title = $this->appendCopySuffix((string)$sourceChapter->chapter_title, 200);
                $targetChapter->status = AppCourseChapter::STATUS_DRAFT;
                $targetChapter->view_count = 0;
                $targetChapter->complete_count = 0;
                $targetChapter->homework_count = 0;
                $targetChapter->created_by = null;
                $targetChapter->updated_by = null;
                $targetChapter->deleted_at = null;
                $targetChapter->deleted_by = null;
                $targetChapter->save();

                $targetVideoContent = $sourceVideoContent->replicate();
                $targetVideoContent->chapter_id = $targetChapter->chapter_id;
                $targetVideoContent->deleted_at = null;
                $targetVideoContent->save();

                $copiedChapterCount++;
                $copiedTotalDuration += (int)$targetChapter->duration;
            }

            $targetCourse->total_chapter = $copiedChapterCount;
            $targetCourse->total_duration = $copiedTotalDuration;
            $targetCourse->save();

            return $targetCourse;
        });
    }

    /**
     * 删除课程-不支持批量删除
     * 软删除
     *
     * @param int $courseId
     * @return int 删除数量
     */
    public function delete(int $courseId): int
    {
        return AppCourseBase::query()
            ->where('course_id', $courseId)
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
                'total_chapter', 'total_duration', 'cover_image', 'teacher_name',
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

    /**
     * 追加复制后缀，并限制最大长度避免写库超长。
     *
     * @param string $title 原标题
     * @param int $maxLength 字段最大长度
     * @return string
     */
    private function appendCopySuffix(string $title, int $maxLength): string
    {
        $suffix = '（复制）';
        $candidate = $title . $suffix;

        if (Str::length($candidate) <= $maxLength) {
            return $candidate;
        }

        $keepLength = max($maxLength - Str::length($suffix), 0);

        return Str::substr($title, 0, $keepLength) . $suffix;
    }

}
