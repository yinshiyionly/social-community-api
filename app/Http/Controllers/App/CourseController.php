<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\App\CourseEnrollRequest;
use App\Http\Resources\App\AppApiResponse;
use App\Http\Resources\App\CourseCategoryResource;
use App\Http\Resources\App\CourseDetailResource;
use App\Http\Resources\App\CourseListResource;
use App\Http\Resources\App\NewCourseListResource;
use App\Services\App\CourseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CourseController extends Controller
{
    /**
     * @var CourseService
     */
    protected $courseService;

    public function __construct(CourseService $courseService)
    {
        $this->courseService = $courseService;
    }

    /**
     * 获取课程详情
     */
    public function detail(Request $request)
    {
        $courseId = (int) $request->input('id', 0);

        if ($courseId <= 0) {
            return AppApiResponse::error('课程ID不能为空');
        }

        try {
            $course = $this->courseService->getCourseFullDetail($courseId);

            if (!$course) {
                return AppApiResponse::dataNotFound('课程不存在');
            }

            // 检查用户是否已拥有该课程
            $memberId = $request->attributes->get('member_id');
            $hasOwned = false;
            if ($memberId) {
                $hasOwned = $this->courseService->checkUserHasCourse($memberId, $courseId);
            }

            return AppApiResponse::resource($course, CourseDetailResource::class, 'success', [
                'hasOwned' => $hasOwned,
            ]);
        } catch (\Exception $e) {
            Log::error('获取课程详情失败', [
                'course_id' => $courseId,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 获取课程分类列表
     */
    public function categories(Request $request)
    {
        try {
            $limit = $request->get('limit', 0);
            $categories = $this->courseService->getCategories($limit);

            return AppApiResponse::collection($categories, CourseCategoryResource::class);
        } catch (\Exception $e) {
            Log::error('获取课程分类列表失败', [
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 选课中心 - 按分类获取课程列表
     */
    public function listByCategory(Request $request)
    {
        $categoryId = (int) $request->input('categoryId', 0);

        if ($categoryId <= 0) {
            return AppApiResponse::error('请选择课程分类');
        }

        try {
            $groups = $this->courseService->getCoursesByCategory($categoryId);

            // 格式化课程数据
            foreach ($groups as &$group) {
                $group['courses'] = CourseListResource::collection($group['courses'])->resolve();
            }

            return AppApiResponse::success(['data' => $groups]);
        } catch (\Exception $e) {
            Log::error('获取选课中心课程列表失败', [
                'category_id' => $categoryId,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 免费领取课程
     */
    public function claim(CourseEnrollRequest $request)
    {
        $memberId = $request->attributes->get('member_id');
        $courseId = (int) $request->input('courseId');
        $phone = $request->input('phone');
        $ageRange = $request->input('ageRange');

        try {
            $memberCourse = $this->courseService->claimFreeCourse($memberId, $courseId, $phone, $ageRange);

            return AppApiResponse::success([
                'data' => [
                    'id' => $memberCourse->id,
                    'courseId' => $memberCourse->course_id,
                    'enrollTime' => $memberCourse->enroll_time->format('Y-m-d H:i:s'),
                ],
            ], '领取成功');
        } catch (\Exception $e) {
            Log::error('免费领取课程失败', [
                'member_id' => $memberId,
                'course_id' => $courseId,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::error($e->getMessage());
        }
    }

    /**
     * 购买课程（获取支付信息）
     */
    public function purchase(CourseEnrollRequest $request)
    {
        $memberId = $request->attributes->get('member_id');
        $courseId = (int) $request->input('courseId');
        $phone = $request->input('phone');
        $ageRange = $request->input('ageRange');

        try {
            $paymentInfo = $this->courseService->preparePurchase($memberId, $courseId, $phone, $ageRange);

            return AppApiResponse::success(['data' => $paymentInfo]);
        } catch (\Exception $e) {
            Log::error('购买课程失败', [
                'member_id' => $memberId,
                'course_id' => $courseId,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::error($e->getMessage());
        }
    }

    /**
     * 获取好课上新列表
     */
    public function newCourses(Request $request)
    {
        $limit = $request->input('limit', 10);

        try {
            $courses = $this->courseService->getNewCourses($limit);

            return AppApiResponse::collection($courses, NewCourseListResource::class);
        } catch (\Exception $e) {
            Log::error('获取好课上新列表失败', [
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 获取名师好课列表
     */
    public function recommendCourses(Request $request)
    {
        $limit = $request->input('limit', 10);

        try {
            $courses = $this->courseService->getRecommendCourses($limit);

            return AppApiResponse::collection($courses, NewCourseListResource::class);
        } catch (\Exception $e) {
            Log::error('获取名师好课列表失败', [
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }
}
