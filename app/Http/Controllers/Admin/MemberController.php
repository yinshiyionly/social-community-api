<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MemberListRequest;
use App\Http\Requests\Admin\MemberOfficialStoreRequest;
use App\Http\Requests\Admin\MemberOfficialUpdateRequest;
use App\Http\Resources\Admin\MemberListResource;
use App\Http\Resources\ApiResponse;
use App\Models\App\AppMemberBase;
use App\Services\Admin\MemberService;
use Illuminate\Support\Facades\Log;

/**
 * 后台会员管理控制器。
 *
 * 职责：
 * 1. 提供 App 会员的后台列表查询能力；
 * 2. 统一处理管理端请求参数并调用会员查询服务；
 * 3. 提供官方会员账号的新增与更新入口；
 * 4. 输出标准化响应供后台会员管理页面使用。
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

    /**
     * 新增官方会员账号。
     *
     * 接口用途：
     * - 后台会员管理页面创建官方账号，用于发帖、系统消息等官方身份场景。
     *
     * 关键输入：
     * - nickname：官方账号昵称；
     * - avatar：官方账号头像（可选）；
     * - officialLabel：官方标签；
     * - status：账号状态（可选，默认正常）。
     *
     * 关键输出：
     * - 返回 ApiResponse::success 结构，data 中包含 memberId。
     *
     * 失败分支：
     * - 当官方ID号段耗尽等可识别业务失败时，返回明确错误文案；
     * - 其他异常记录日志后返回通用错误，避免暴露内部异常细节。
     *
     * @param MemberOfficialStoreRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeOfficial(MemberOfficialStoreRequest $request)
    {
        $payload = [
            'nickname' => (string) $request->input('nickname'),
            'avatar' => (string) $request->input('avatar', ''),
            'official_label' => (string) $request->input('officialLabel'),
            'status' => (int) $request->input('status', AppMemberBase::STATUS_NORMAL),
        ];

        try {
            $member = $this->memberService->createOfficial($payload);

            return ApiResponse::success([
                'data' => [
                    'memberId' => (int) $member->member_id,
                ],
            ], '新增成功');
        } catch (\RuntimeException $e) {
            Log::warning('新增官方会员失败（业务约束）', [
                'action' => 'storeOfficial',
                'payload' => $payload,
                'error' => $e->getMessage(),
            ]);

            return ApiResponse::error($e->getMessage());
        } catch (\Exception $e) {
            Log::error('新增官方会员失败', [
                'action' => 'storeOfficial',
                'payload' => $payload,
                'error' => $e->getMessage(),
            ]);

            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 更新官方会员账号（部分更新）。
     *
     * 接口用途：
     * - 后台会员管理页面维护官方账号的昵称、头像、标签与状态。
     *
     * 关键输入：
     * - memberId：目标官方会员ID；
     * - 其余字段按需传入，未传字段保持原值。
     *
     * 关键输出：
     * - 返回 ApiResponse::success 结构，data 中包含 memberId。
     *
     * 失败分支：
     * - 目标账号不存在或非官方账号时返回“官方会员不存在”；
     * - 其他异常记录日志后返回通用错误，避免暴露内部异常细节。
     *
     * @param MemberOfficialUpdateRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateOfficial(MemberOfficialUpdateRequest $request)
    {
        $memberId = (int) $request->input('memberId');
        $payload = [];

        // 仅映射前端显式传入字段，避免未传字段被误覆盖。
        if ($request->exists('nickname')) {
            $payload['nickname'] = (string) $request->input('nickname');
        }
        if ($request->exists('avatar')) {
            $payload['avatar'] = (string) $request->input('avatar');
        }
        if ($request->exists('officialLabel')) {
            $payload['official_label'] = (string) $request->input('officialLabel');
        }
        if ($request->exists('status')) {
            $payload['status'] = (int) $request->input('status');
        }

        try {
            $updated = $this->memberService->updateOfficial($memberId, $payload);

            if (!$updated) {
                return ApiResponse::error('官方会员不存在');
            }

            return ApiResponse::success([
                'data' => [
                    'memberId' => $memberId,
                ],
            ], '修改成功');
        } catch (\Exception $e) {
            Log::error('更新官方会员失败', [
                'action' => 'updateOfficial',
                'member_id' => $memberId,
                'payload' => $payload,
                'error' => $e->getMessage(),
            ]);

            return ApiResponse::error('操作失败，请稍后重试');
        }
    }
}
