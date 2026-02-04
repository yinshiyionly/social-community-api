<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CourseStoreRequest;
use App\Http\Requests\Admin\CourseUpdateRequest;
use App\Http\Requests\Admin\CourseStatusRequest;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\Admin\CourseResource;
use App\Http\Resources\Admin\CourseListResource;
use App\Http\Resources\Admin\CourseSimpleResource;
use App\Services\Admin\CourseService;
use App\Services\Admin\CourseCategoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
     * 课程列表
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function list(Request $request)
    {
        $filters = [
            'courseTitle' => $request->input('courseTitle'),
            'courseNo' => $request->input('courseNo'),
            'categoryId' => $request->input('categoryId'),
            'payType' => $request->input('payType'),
            'playType' => $request->input('playType'),
            'status' => $request->input('status'),
            'isFree' => $request->input('isFree'),
            'isRecommend' => $request->input('isRecommend'),
            'beginTime' => $request->input('beginTime'),
            'endTime' => $request->input('endTime'),
        ];

        $pageNum = (int) $request->input('pageNum', 1);
        $pageSize = (int) $request->input('pageSize', 10);

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
        $course = $this->courseService->getDetail((int) $courseId);

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
            // 检查分类是否存在
            $categoryId = $request->input('categoryId');
            if (!$this->categoryService->exists($categoryId)) {
                return ApiResponse::error('课程分类不存在');
            }

            $data = [
                'categoryId' => $categoryId,
                'courseTitle' => $request->input('courseTitle'),
                'courseSubtitle' => $request->input('courseSubtitle'),
                'payType' => $request->input('payType'),
                'playType' => $request->input('playType'),
                'scheduleType' => $request->input('scheduleType'),
                'coverImage' => $request->input('coverImage'),
                'coverVideo' => $request->input('coverVideo'),
                'bannerImages' => $request->input('bannerImages'),
                'introVideo' => $request->input('introVideo'),
                'brief' => $request->input('brief'),
                'description' => $request->input('description'),
                'suitableCrowd' => $request->input('suitableCrowd'),
                'learnGoal' => $request->input('learnGoal'),
                'teacherId' => $request->input('teacherId'),
                'assistantIds' => $request->input('assistantIds'),
                'originalPrice' => $request->input('originalPrice'),
                'currentPrice' => $request->input('currentPrice'),
                'pointPrice' => $request->input('pointPrice'),
                'isFree' => $request->input('isFree'),
                'validDays' => $request->input('validDays'),
                'allowDownload' => $request->input('allowDownload'),
                'allowComment' => $request->input('allowComment'),
                'allowShare' => $request->input('allowShare'),
                'sortOrder' => $request->input('sortOrder'),
                'isRecommend' => $request->input('isRecommend'),
                'isHot' => $request->input('isHot'),
                'isNew' => $request->input('isNew'),
            ];

            $course = $this->courseService->create($data);

            return ApiResponse::success(['courseId' => $course->course_id], '新增成功');
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
            $courseId = (int) $request->input('courseId');

            // 检查分类是否存在
            $categoryId = $request->input('categoryId');
            if ($categoryId !== null && !$this->categoryService->exists($categoryId)) {
                return ApiResponse::error('课程分类不存在');
            }

            $data = [
                'categoryId' => $categoryId,
                'courseTitle' => $request->input('courseTitle'),
                'courseSubtitle' => $request->input('courseSubtitle'),
                'payType' => $request->input('payType'),
                'playType' => $request->input('playType'),
                'scheduleType' => $request->input('scheduleType'),
                'coverImage' => $request->input('coverImage'),
                'coverVideo' => $request->input('coverVideo'),
                'bannerImages' => $request->input('bannerImages'),
                'introVideo' => $request->input('introVideo'),
                'brief' => $request->input('brief'),
                'description' => $request->input('description'),
                'suitableCrowd' => $request->input('suitableCrowd'),
                'learnGoal' => $request->input('learnGoal'),
                'teacherId' => $request->input('teacherId'),
                'assistantIds' => $request->input('assistantIds'),
                'originalPrice' => $request->input('originalPrice'),
                'currentPrice' => $request->input('currentPrice'),
                'pointPrice' => $request->input('pointPrice'),
                'isFree' => $request->input('isFree'),
                'validDays' => $request->input('validDays'),
                'allowDownload' => $request->input('allowDownload'),
                'allowComment' => $request->input('allowComment'),
                'allowShare' => $request->input('allowShare'),
                'sortOrder' => $request->input('sortOrder'),
                'isRecommend' => $request->input('isRecommend'),
                'isHot' => $request->input('isHot'),
                'isNew' => $request->input('isNew'),
            ];

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
     * 删除课程（支持批量）
     *
     * @param string $courseIds 逗号分隔的课程ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($courseIds)
    {
        try {
            $ids = array_map('intval', explode(',', $courseIds));

            // 检查是否有章节
            foreach ($ids as $id) {
                if ($this->courseService->hasChapters($id)) {
                    return ApiResponse::error('课程下存在章节，无法删除');
                }
            }

            $deletedCount = $this->courseService->delete($ids);

            if ($deletedCount > 0) {
                return ApiResponse::success([], '删除成功');
            } else {
                return ApiResponse::error('删除失败，课程不存在');
            }
        } catch (\Exception $e) {
            Log::error('删除课程失败', [
                'action' => 'destroy',
                'course_ids' => $courseIds,
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
            $courseId = (int) $request->input('courseId');
            $status = (int) $request->input('status');

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
     * 下拉选项列表
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function optionselect()
    {
        $options = $this->courseService->getOptions();

        return ApiResponse::collection($options, CourseSimpleResource::class, '查询成功');
    }
}
