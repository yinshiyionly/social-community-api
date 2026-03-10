<?php

namespace App\Http\Controllers\App;

use App\Constant\AppResponseCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\CourseEnrollRequest;
use App\Http\Requests\App\CourseOrderRefundRequest;
use App\Http\Requests\App\CourseOrderStatusRequest;
use App\Http\Resources\App\AppApiResponse;
use App\Http\Resources\App\CourseCategoryResource;
use App\Http\Resources\App\CourseDetailResource;
use App\Http\Resources\App\CourseListResource;
use App\Http\Resources\App\LiveCourseListResource;
use App\Http\Resources\App\NewCourseListResource;
use App\Services\App\CourseOrderService;
use App\Services\App\CourseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CourseController extends Controller
{
    /**
     * @var CourseService
     */
    protected $courseService;

    /**
     * @var CourseOrderService
     */
    protected $courseOrderService;

    public function __construct(CourseService $courseService, CourseOrderService $courseOrderService)
    {
        $this->courseService = $courseService;
        $this->courseOrderService = $courseOrderService;
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
     * 获取入学信息详情
     */
    public function enrollmentDetail(Request $request)
    {
        $courseId = (int) $request->input('id', 0);
        if ($courseId <= 0) {
            return AppApiResponse::error('参数错误', AppResponseCode::INVALID_PARAMS);
        }

        $memberId = (int) $request->attributes->get('member_id', 0);
        if ($memberId <= 0) {
            return AppApiResponse::unauthorized();
        }

        try {
            $data = $this->courseService->getEnrollmentDetail($memberId, $courseId);

            if (!$data) {
                return AppApiResponse::dataNotFound('课程不存在');
            }

            return AppApiResponse::success(['data' => $data]);
        } catch (\Exception $e) {
            Log::error('获取入学信息详情失败', [
                'member_id' => $memberId,
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
                $group['list'] = CourseListResource::collection($group['list'])->resolve();
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
        $courseId = (int) $request->input('id');
        $phone = $request->input('phone');
        $ageRange = $request->input('age');

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
        $memberId = (int)$request->attributes->get('member_id');
        // 前端传递的是 id
        // 等同于 courseId
        $courseId = (int) $request->input('id');
        $phone = $request->input('phone');
        $ageRange = $request->input('age');

        try {
            $paymentInfo = $this->courseOrderService->createWechatAppOrder(
                $memberId,
                $courseId,
                $phone,
                $ageRange,
                $request->ip(),
                (string)$request->userAgent()
            );

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
     * 查询订单状态
     */
    public function orderStatus(CourseOrderStatusRequest $request)
    {
        $memberId = (int)$request->attributes->get('member_id');
        $orderNo = (string)$request->input('orderNo');

        try {
            $data = $this->courseOrderService->getOrderStatus($memberId, $orderNo);

            return AppApiResponse::success(['data' => $data]);
        } catch (\Exception $e) {
            Log::error('查询课程订单状态失败', [
                'member_id' => $memberId,
                'order_no' => $orderNo,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::error($e->getMessage());
        }
    }

    /**
     * 课程订单退款
     */
    public function refund(CourseOrderRefundRequest $request)
    {
        $memberId = (int)$request->attributes->get('member_id');
        $orderNo = (string)$request->input('orderNo');
        $reason = trim((string)$request->input('reason', ''));

        try {
            $data = $this->courseOrderService->refundWechatOrder($memberId, $orderNo, $reason, $request->ip());

            return AppApiResponse::success(['data' => $data], '退款成功');
        } catch (\Exception $e) {
            Log::error('课程订单退款失败', [
                'member_id' => $memberId,
                'order_no' => $orderNo,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::error($e->getMessage());
        }
    }

    /**
     * 微信支付回调
     */
    public function wechatPayNotify(Request $request)
    {
        $rawXml = (string)$request->getContent();

        try {
            $this->courseOrderService->handleWechatNotify($rawXml, $request->ip());

            $xml = $this->courseOrderService->getWechatNotifySuccessXml();

            return response($xml, 200, [
                'Content-Type' => 'application/xml; charset=utf-8',
            ]);
        } catch (\Exception $e) {
            Log::error('微信支付回调处理失败', [
                'error' => $e->getMessage(),
            ]);

            $xml = $this->courseOrderService->getWechatNotifyFailXml($e->getMessage());

            return response($xml, 200, [
                'Content-Type' => 'application/xml; charset=utf-8',
            ]);
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

    /**
     * 获取大咖直播列表
     */
    public function liveCourses(Request $request)
    {
        $limit = $request->input('limit', 10);

        try {
            $courses = $this->courseService->getLiveCourses($limit);

            return AppApiResponse::collection($courses, LiveCourseListResource::class);
        } catch (\Exception $e) {
            Log::error('获取大咖直播列表失败', [
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }

}
