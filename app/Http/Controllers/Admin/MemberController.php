<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MemberListRequest;
use App\Http\Resources\Admin\MemberListResource;
use App\Http\Resources\ApiResponse;
use App\Services\Admin\MemberService;
use Illuminate\Support\Facades\Log;

/**
 * 后台会员管理控制器。
 *
 * 职责：
 * 1. 提供 App 会员的后台列表查询能力；
 * 2. 统一处理管理端请求参数并调用会员查询服务；
 * 3. 输出标准化分页响应供后台用户管理页面使用。
 */
class MemberController extends Controller
{
    /**
     * @var MemberService
     */
    protected $memberService;

    /**
     * @param MemberService $memberService
     */
    public function __construct(MemberService $memberService)
    {
        $this->memberService = $memberService;
    }

    /**
     * 用户列表（分页）。
     *
     * 接口用途：
     * - 后台【用户管理-用户列表】页面查询 App 端会员数据。
     *
     * 关键输入：
     * - 筛选参数：memberId、phone、nickname、status、isOfficial、beginTime、endTime；
     * - 分页参数：pageNum、pageSize。
     *
     * 关键输出：
     * - 返回 ApiResponse::paginate 结构（code、msg、total、rows）。
     *
     * 失败分支：
     * - 数据库或服务异常时记录日志并返回通用错误，避免向前端暴露内部实现细节。
     *
     * @param MemberListRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function list(MemberListRequest $request)
    {
        $filters = [
            'memberId' => $request->input('memberId'),
            'phone' => $request->input('phone'),
            'nickname' => $request->input('nickname'),
            'status' => $request->input('status'),
            'isOfficial' => $request->input('isOfficial'),
            'beginTime' => $request->input('beginTime'),
            'endTime' => $request->input('endTime'),
        ];

        $pageNum = (int) $request->input('pageNum', 1);
        $pageSize = (int) $request->input('pageSize', 10);

        try {
            $paginator = $this->memberService->getList($filters, $pageNum, $pageSize);

            return ApiResponse::paginate($paginator, MemberListResource::class, '查询成功');
        } catch (\Exception $e) {
            Log::error('查询会员列表失败', [
                'action' => 'list',
                'filters' => $filters,
                'error' => $e->getMessage(),
            ]);

            // 记录后端详细异常，接口层返回通用文案，降低内部信息暴露风险。
            return ApiResponse::error('操作失败，请稍后重试');
        }
    }
}

