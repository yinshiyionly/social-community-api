<?php

namespace App\Http\Controllers\App;

use App\Constant\AppResponseCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\CourseDetailRequest;
use App\Http\Requests\App\CourseEnrollRequest;
use App\Http\Requests\App\CourseOrderListRequest;
use App\Http\Requests\App\CourseOrderRefundRequest;
use App\Http\Requests\App\CourseOrderStatusRequest;
use App\Http\Resources\App\AppApiResponse;
use App\Http\Resources\App\CourseCategoryResource;
use App\Http\Resources\App\CourseDetailResource;
use App\Http\Resources\App\CourseListResource;
use App\Http\Resources\App\CourseOrderListResource;
use App\Http\Resources\App\LiveCourseListResource;
use App\Http\Resources\App\NewCourseListResource;
use App\Services\App\CourseOrderService;
use App\Services\App\CourseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * App 端课程与课程订单控制器。
 *
 * 职责：
 * 1. 提供课程分类、详情、上新、推荐及直播列表等课程查询接口；
 * 2. 提供课程领取、下单、订单列表、状态查询与退款等订单能力；
 * 3. 统一在控制器层处理异常日志与响应结构，避免暴露内部实现细节。
 */
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
     * 判断课程是否存在在线章节。
     *
     * 规则：
     * 1. 仅统计 status=online 且未软删的章节；
     * 2. 课程不存在时返回数据不存在响应；
     * 3. 响应 data 直接返回 boolean，便于前端直接分流详情接口。
     *
     * @param CourseDetailRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function hasChapters(CourseDetailRequest $request)
    {
        $courseId = $request->getCourseId();

        try {
            $hasChapters = $this->courseService->hasOnlineChapters($courseId);
            if (is_null($hasChapters)) {
                return AppApiResponse::dataNotFound('课程不存在');
            }

            return AppApiResponse::success(['data' => $hasChapters]);
        } catch (\Exception $e) {
            Log::error('判断课程是否有章节失败', [
                'course_id' => $courseId,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 获取无章节课程详情（旧版长图详情）。
     *
     * 约束：
     * 1. contentImage 通过 item_image -> banner_images[0] 兜底；
     * 2. 详情访问会累计课程浏览量；
     * 3. 课程不存在时返回数据不存在响应。
     *
     * @param CourseDetailRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function detailLegacy(CourseDetailRequest $request)
    {
        $courseId = $request->getCourseId();

        try {
            $data = $this->courseService->getLegacyDetailData($courseId);
            if (is_null($data)) {
                return AppApiResponse::dataNotFound('课程不存在');
            }

            return AppApiResponse::success(['data' => $data]);
        } catch (\Exception $e) {
            Log::error('获取无章节课程详情失败', [
                'course_id' => $courseId,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 获取有章节课程详情（章节版）。
     *
     * 约束：
     * 1. 章节版详情允许游客访问，登录态会返回真实 isUnlocked；
     * 2. 详情访问会累计课程浏览量；
     * 3. 课程不存在时返回数据不存在响应。
     *
     * @param CourseDetailRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function detailChapters(CourseDetailRequest $request)
    {
        $courseId = $request->getCourseId();
        $memberId = (int)$request->attributes->get('member_id', 0);

        try {
            $data = $this->courseService->getChapterDetailData($courseId, $memberId);
            if (is_null($data)) {
                return AppApiResponse::dataNotFound('课程不存在');
            }

            return AppApiResponse::success(['data' => $data]);
        } catch (\Exception $e) {
            Log::error('获取有章节课程详情失败', [
                'course_id' => $courseId,
                'member_id' => $memberId,
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
     * 获取当前登录用户课程订单列表。
     *
     * 接口约束：
     * 1. 仅返回当前登录用户订单，避免跨用户数据泄露；
     * 2. status 支持 unpaid/paid/closed/refunded 四种筛选值；
     * 3. 响应结构固定为 data.list/total/page/pageSize，供“我的订单”页直接渲染。
     *
     * 失败策略：
     * - 记录上下文日志后返回通用错误，避免暴露内部异常细节。
     *
     * @param CourseOrderListRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function orderList(CourseOrderListRequest $request)
    {
        $memberId = (int)$request->attributes->get('member_id', 0);
        if ($memberId <= 0) {
            return AppApiResponse::unauthorized();
        }

        $page = $request->getPage();
        $pageSize = $request->getPageSize();
        $status = $request->getStatus();

        try {
            $paginator = $this->courseOrderService->getMemberOrderList($memberId, $page, $pageSize, $status);
            $list = CourseOrderListResource::collection(collect($paginator->items()))->resolve();

            return AppApiResponse::success([
                'data' => [
                    'list' => $list,
                    'total' => $paginator->total(),
                    'page' => $paginator->currentPage(),
                    'pageSize' => $paginator->perPage(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('查询我的课程订单列表失败', [
                'member_id' => $memberId,
                'page' => $page,
                'page_size' => $pageSize,
                'status' => $status,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
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
     * 获取课程页大咖直播卡片列表。
     *
     * 接口约束：
     * 1. limit 为每种状态（live/upcoming/replay）的条数上限，默认 2；
     * 2. 接口允许游客访问，登录态通过可选鉴权返回真实 isReserved；
     * 3. 返回结构使用 AppApiResponse::collection，data 为卡片数组。
     *
     * 失败策略：
     * - 记录必要上下文日志后统一返回通用错误，避免暴露内部异常细节。
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function liveCourses(Request $request)
    {
        $limit = (int)$request->input('limit', 2);
        if ($limit <= 0) {
            $limit = 2;
        }

        $memberId = (int)$request->attributes->get('member_id', 0);

        try {
            $courses = $this->courseService->getLiveCourses($limit, $memberId);

            return AppApiResponse::collection($courses, LiveCourseListResource::class);
        } catch (\Exception $e) {
            Log::error('获取大咖直播列表失败', [
                'limit' => $limit,
                'member_id' => $memberId,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }

}
