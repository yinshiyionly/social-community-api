<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CourseBatchSortRequest;
use App\Http\Requests\Admin\CourseStoreRequest;
use App\Http\Requests\Admin\CourseUpdateRequest;
use App\Http\Requests\Admin\CourseStatusRequest;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\Admin\CourseResource;
use App\Http\Resources\Admin\CourseListResource;
use App\Http\Resources\Admin\CourseScheduleResource;
use App\Http\Resources\Admin\CourseSimpleResource;
use App\Models\App\AppCourseBase;
use App\Services\Admin\CourseService;
use App\Services\Admin\CourseCategoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * 后台课程管理控制器。
 *
 * 职责：
 * 1. 提供课程列表、详情、创建、更新、删除等管理端接口；
 * 2. 承接参数校验后的业务编排，调用 Service 完成课程写入与查询；
 * 3. 对外统一返回 ApiResponse 结构，避免暴露内部异常细节。
 */
class CourseController extends Controller
{
    /**
     * @var CourseService
     */
    protected $courseService;

    /**
     * @var CourseCategoryService
     */
    protected $categoryService;

    /**
     * CourseController constructor.
     *
     * @param CourseService $courseService
     * @param CourseCategoryService $categoryService
     */
    public function __construct(CourseService $courseService, CourseCategoryService $categoryService)
    {
        $this->courseService = $courseService;
        $this->categoryService = $categoryService;
    }

    /**
     * 课程常量选项（付费类型、播放类型、排课类型、状态）
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function constants()
    {
        $data = [
            // 付费类型
            'payTypeOptions' => [
                ['label' => '招生0元课', 'value' => AppCourseBase::PAY_TYPE_TRIAL],
                ['label' => '进阶课', 'value' => AppCourseBase::PAY_TYPE_ADVANCED],
                ['label' => '高阶课', 'value' => AppCourseBase::PAY_TYPE_HIGHER],
            ],
            // 播放类型
            'playTypeOptions' => [
                ['label' => '直播课', 'value' => AppCourseBase::PLAY_TYPE_LIVE],
                ['label' => '录播课', 'value' => AppCourseBase::PLAY_TYPE_VIDEO],
                // ['label' => '图文课', 'value' => AppCourseBase::PLAY_TYPE_ARTICLE],
                // ['label' => '音频课', 'value' => AppCourseBase::PLAY_TYPE_AUDIO],
            ],
            // 是否免费课程
            'isFreeOptions' => [
                ['label' => '免费课', 'value' => 1],
                ['label' => '付费课', 'value' => 0],
            ],
            // 排课类型
            'scheduleTypeOptions' => [
                ['label' => '固定日期', 'value' => AppCourseBase::SCHEDULE_TYPE_FIXED],
                ['label' => '动态解锁', 'value' => AppCourseBase::SCHEDULE_TYPE_DYNAMIC],
            ],
            // 状态
            'statusOptions' => [
                ['label' => '草稿', 'value' => AppCourseBase::STATUS_DRAFT],
                ['label' => '上架', 'value' => AppCourseBase::STATUS_ONLINE],
                ['label' => '下架', 'value' => AppCourseBase::STATUS_OFFLINE],
            ],
        ];

        return ApiResponse::success(['data' => $data], '查询成功');
    }

    /**
     * 课程列表
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function list(Request $request)
    {
        $filters = [
            'courseTitle' => $request->input('courseTitle'),
            'categoryId' => $request->input('categoryId'),
            'payType' => $request->input('payType'),
            'playType' => $request->input('playType'),
            'status' => $request->input('status'),
            'isFree' => $request->input('isFree'),
            'beginTime' => $request->input('beginTime'),
            'endTime' => $request->input('endTime'),
        ];

        $pageNum = (int)$request->input('pageNum', 1);
        $pageSize = (int)$request->input('pageSize', 10);

        $paginator = $this->courseService->getList($filters, $pageNum, $pageSize);

        return ApiResponse::paginate($paginator, CourseListResource::class, '查询成功');
    }

    /**
     * 课程详情
     *
     * @param int $courseId
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($courseId)
    {
        $course = $this->courseService->getDetail((int)$courseId);

        if (!$course) {
            return ApiResponse::error('课程不存在');
        }

        return ApiResponse::resource($course, CourseResource::class, '查询成功');
    }

    /**
     * 新增课程
     *
     * @param CourseStoreRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(CourseStoreRequest $request)
    {
        try {
            $validated = $request->validated();

            // 检查分类是否存在
            $categoryId = (int)$validated['categoryId'];
            if (!$this->categoryService->exists($categoryId)) {
                return ApiResponse::error('课程分类不存在');
            }

            $data = $this->buildCoursePayload($validated);

            $course = $this->courseService->create($data);

            return ApiResponse::success([
                'data' => [
                    'courseId' => $course->course_id,
                ],
            ], '新增成功');
        } catch (\Exception $e) {
            Log::error('新增课程失败', [
                'action' => 'store',
                'error' => $e->getMessage(),
            ]);
            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 更新课程
     *
     * @param CourseUpdateRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(CourseUpdateRequest $request)
    {
        try {
            $validated = $request->validated();
            $courseId = (int)$validated['courseId'];
            $categoryId = (int)$validated['categoryId'];

            // 更新接口与创建接口保持同一必填集合，因此这里固定校验分类存在性。
            if (!$this->categoryService->exists($categoryId)) {
                return ApiResponse::error('课程分类不存在');
            }

            // 基于 validated 组装数据，只处理通过校验且显式提交的字段。
            $data = $this->buildCoursePayload($validated);

            $result = $this->courseService->update($courseId, $data);

            if (!$result) {
                return ApiResponse::error('课程不存在');
            }

            return ApiResponse::success([], '修改成功');
        } catch (\Exception $e) {
            Log::error('更新课程失败', [
                'action' => 'update',
                'course_id' => $request->input('courseId'),
                'error' => $e->getMessage(),
            ]);
            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 删除课程-不支持批量删除
     *
     * @param int $courseId 课程ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($courseId)
    {
        try {
            $courseId = (int) $courseId;

            // 检查是否有章节
            if ($this->courseService->hasChapters($courseId)) {
                return ApiResponse::error('课程下存在章节，无法删除');
            }

            $deletedCount = $this->courseService->delete($courseId);

            if ($deletedCount > 0) {
                return ApiResponse::success([], '删除成功');
            } else {
                return ApiResponse::error('删除失败，课程不存在');
            }
        } catch (\Exception $e) {
            Log::error('删除课程失败', [
                'action' => 'destroy',
                'course_id' => $courseId,
                'error' => $e->getMessage(),
            ]);
            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 修改课程状态
     *
     * @param CourseStatusRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changeStatus(CourseStatusRequest $request)
    {
        try {
            $courseId = (int)$request->input('courseId');
            $status = (int)$request->input('status');

            $result = $this->courseService->changeStatus($courseId, $status);

            if (!$result) {
                return ApiResponse::error('课程不存在');
            }

            return ApiResponse::success([], '修改成功');
        } catch (\Exception $e) {
            Log::error('修改课程状态失败', [
                'action' => 'changeStatus',
                'course_id' => $request->input('courseId'),
                'error' => $e->getMessage(),
            ]);
            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 批量更新课程排序
     *
     * @param CourseBatchSortRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function batchSort(CourseBatchSortRequest $request)
    {
        try {
            $course = $request->input('course', []);

            $this->courseService->batchUpdateSort($course);

            return ApiResponse::success([], '排序成功');
        } catch (\Exception $e) {
            Log::error('批量更新课程排序失败', [
                'action' => 'batchSort',
                'error' => $e->getMessage(),
            ]);
            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 下拉选项列表
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function optionselect()
    {
        $options = $this->courseService->getOptions();

        return ApiResponse::collection($options, CourseSimpleResource::class, '查询成功');
    }

    /**
     * 课表详情
     *
     * @param int $courseId
     * @return \Illuminate\Http\JsonResponse
     */
    public function schedule(int $courseId)
    {
        $course = $this->courseService->getSchedule($courseId);

        if (!$course) {
            return ApiResponse::notFound('课程不存在');
        }

        return ApiResponse::resource($course, CourseScheduleResource::class, '查询成功');
    }

    /**
     * 组装课程创建/更新载荷。
     *
     * 规则：
     * 1. 必填字段固定透传，保证创建与更新语义一致；
     * 2. 可选字段仅在显式传入时写入，避免更新时误清空存量值。
     *
     * @param array<string, mixed> $validated
     * @return array<string, mixed>
     */
    private function buildCoursePayload(array $validated): array
    {
        $data = [
            'categoryId' => $validated['categoryId'],
            'courseTitle' => $validated['courseTitle'],
            'payType' => $validated['payType'],
            'playType' => $validated['playType'],
            'scheduleType' => $validated['scheduleType'],
            'teacherName' => $validated['teacherName'],
            'classTeacherName' => $validated['classTeacherName'],
            'classTeacherQr' => $validated['classTeacherQr'],
            'coverImage' => $validated['coverImage'],
            'itemImage' => $validated['itemImage'],
            'description' => $validated['description'],
            'originalPrice' => $validated['originalPrice'],
            'currentPrice' => $validated['currentPrice'],
            'isFree' => $validated['isFree'],
            'status' => $validated['status'],
        ];

        if (array_key_exists('courseSubtitle', $validated)) {
            $data['courseSubtitle'] = $validated['courseSubtitle'];
        }
        if (array_key_exists('remark', $validated)) {
            $data['remark'] = $validated['remark'];
        }
        if (array_key_exists('publishTime', $validated)) {
            $data['publishTime'] = $validated['publishTime'];
        }

        return $data;
    }

}
