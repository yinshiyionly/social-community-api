<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\App\StudyCourseDetailRequest;
use App\Http\Requests\App\StudyCourseLearnRequest;
use App\Http\Requests\App\StudyCourseListRequest;
use App\Http\Resources\App\AppApiResponse;
use App\Services\App\StudyCourseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * 学习页（课程Tab）控制器
 */
class StudyCourseController extends Controller
{
    /**
     * @var StudyCourseService
     */
    protected $studyCourseService;

    public function __construct(StudyCourseService $service)
    {
        $this->studyCourseService = $service;
    }

    /**
     * 获取课程分类筛选项
     */
    public function filters()
    {
        try {
            $data = $this->studyCourseService->getCourseFilters();

            return AppApiResponse::success(['data' => $data]);
        } catch (\Exception $e) {
            Log::error('获取课程分类筛选项失败', [
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 获取课程付费类型筛选项
     */
    public function allTypes()
    {
        try {
            $data = $this->studyCourseService->getCoursePayTypes();

            return AppApiResponse::success(['data' => $data]);
        } catch (\Exception $e) {
            Log::error('获取课程付费类型筛选项失败', [
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 获取学习中心课程详情（头部信息 + 每日计划）。
     *
     * 访问约束：
     * 1. 仅允许已领取/已购买该课程的用户访问；
     * 2. 课程不存在返回数据不存在响应；
     * 3. 未拥有课程返回无权访问响应。
     *
     * @param StudyCourseDetailRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail(StudyCourseDetailRequest $request)
    {
        $memberId = (int)$request->attributes->get('member_id');
        $courseId = (int)$request->input('courseId');
        $planKey = $request->input('planKey');

        try {
            $course = $this->studyCourseService->getLearningCourseBase($courseId);
            if (!$course) {
                return AppApiResponse::dataNotFound('课程不存在');
            }

            if (!$this->studyCourseService->checkMemberHasCourse($memberId, $courseId)) {
                return AppApiResponse::forbidden('您未拥有该课程');
            }

            $data = $this->studyCourseService->buildLearningCourseDetail($memberId, $course, $planKey);

            return AppApiResponse::success(['data' => $data]);
        } catch (\Exception $e) {
            Log::error('获取学习中心课程详情失败', [
                'member_id' => $memberId,
                'course_id' => $courseId,
                'plan_key' => $planKey,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 上报章节学习完成状态。
     *
     * 接口用途：
     * - 将用户在某课程下某章节的学习状态流转为“已学习”，并同步课程汇总进度。
     *
     * 关键约束：
     * 1. 仅允许已拥有课程的用户上报；
     * 2. 章节必须属于课程且存在对应章节课表；
     * 3. 未解锁章节拒绝上报，防止绕过排课规则。
     *
     * @param StudyCourseLearnRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function learn(StudyCourseLearnRequest $request)
    {
        $memberId = (int)$request->attributes->get('member_id');
        $courseId = (int)$request->input('courseId');
        $chapterId = (int)$request->input('chapterId');

        try {
            $data = $this->studyCourseService->markChapterLearned($memberId, $courseId, $chapterId);
            return AppApiResponse::success(['data' => $data]);
        } catch (\DomainException $e) {
            $errorType = (string)$e->getMessage();

            if ($errorType === 'course_not_owned') {
                return AppApiResponse::forbidden('您未拥有该课程');
            }

            if ($errorType === 'chapter_not_found') {
                return AppApiResponse::dataNotFound('章节不存在');
            }

            if ($errorType === 'schedule_not_found') {
                return AppApiResponse::dataNotFound('章节课表不存在');
            }

            if ($errorType === 'schedule_not_unlocked') {
                return AppApiResponse::error('章节未解锁，暂不可学习');
            }

            Log::warning('章节学习上报业务异常（未知类型）', [
                'member_id' => $memberId,
                'course_id' => $courseId,
                'chapter_id' => $chapterId,
                'error_type' => $errorType,
            ]);

            return AppApiResponse::error('学习上报失败');
        } catch (\Exception $e) {
            Log::error('章节学习上报失败', [
                'member_id' => $memberId,
                'course_id' => $courseId,
                'chapter_id' => $chapterId,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 获取今日学习任务（课程维度）。
     *
     * 接口用途：
     * - 学习页顶部“今日学习任务”卡片数据源。
     *
     * 关键约束：
     * 1. 仅读取当前登录用户（member_id）当天课表；
     * 2. 返回结构由服务层统一聚合为课程维度，包含课程卡片通用字段；
     * 3. 保留历史兼容字段，前端可渐进切换。
     *
     * 失败分支：
     * - 捕获异常后记录日志并返回通用错误，避免向客户端暴露内部异常细节。
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function todayTasks(Request $request)
    {
        $memberId = $request->attributes->get('member_id');

        try {
            $data = $this->studyCourseService->getTodayTasks($memberId);

            return AppApiResponse::success(['data' => $data]);
        } catch (\Exception $e) {
            Log::error('获取今日学习任务失败', [
                'member_id' => $memberId,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 获取学习页默认分组数据（最近学习 / 待学习 / 已结课）。
     *
     * 接口用途：
     * - 学习页课程Tab默认态分组展示。
     *
     * 关键约束：
     * 1. 分组判定沿用既有业务语义；
     * 2. 展示维度统一为课程，组内按课程去重；
     * 3. 响应结构继续返回分组标题与列表，兼容前端渲染逻辑。
     *
     * 失败分支：
     * - 捕获异常后记录日志并返回通用错误，避免泄露内部实现细节。
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sections(Request $request)
    {
        $memberId = $request->attributes->get('member_id');

        try {
            $data = $this->studyCourseService->getCourseSections($memberId);

            return AppApiResponse::success(['data' => $data]);
        } catch (\Exception $e) {
            Log::error('获取学习页分组数据失败', [
                'member_id' => $memberId,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }

    /**
     * 获取学习页筛选后的课程列表（分页）。
     *
     * 接口用途：
     * - 按课程分类与付费类型返回学习页课程卡片。
     *
     * 关键输入：
     * - filter：课程分类ID；
     * - filterType：课程付费类型；
     * - page/pageSize：分页参数。
     *
     * 关键输出：
     * - data.list 为课程维度卡片集合；
     * - data.total/page/pageSize 保持原分页语义。
     *
     * 失败分支：
     * - 捕获异常后记录日志并返回通用错误，避免向客户端透传内部异常。
     *
     * @param StudyCourseListRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function list(StudyCourseListRequest $request)
    {
        $memberId = $request->attributes->get('member_id');
        $categoryId = $request->input('filter');
        $payType = $request->input('filterType');
        $page = (int) $request->input('page', 1);
        $pageSize = (int) $request->input('pageSize', 10);

        try {
            $data = $this->studyCourseService->getFilteredCourseList(
                $memberId,
                $categoryId,
                $payType,
                $page,
                $pageSize
            );

            return AppApiResponse::success(['data' => $data]);
        } catch (\Exception $e) {
            Log::error('获取学习页课程列表失败', [
                'member_id' => $memberId,
                'category_id' => $categoryId,
                'pay_type' => $payType,
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }

}
