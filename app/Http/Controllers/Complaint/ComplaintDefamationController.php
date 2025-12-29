<?php

declare(strict_types=1);

namespace App\Http\Controllers\Complaint;

use App\Http\Controllers\Controller;
use App\Http\Requests\Complaint\ComplaintDefamation\CreateComplaintDefamationRequest;
use App\Http\Requests\Complaint\ComplaintDefamation\UpdateComplaintDefamationRequest;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\Complaint\ComplaintDefamation\ComplaintDefamationItemResource;
use App\Http\Resources\Complaint\ComplaintDefamation\ComplaintDefamationListResource;
use App\Jobs\Complaint\ComplaintDefamationSendMailJob;
use App\Services\Complaint\ComplaintDefamationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 诽谤类投诉控制器
 *
 * 处理诽谤类投诉相关的HTTP请求，包括CRUD操作和发件邮箱列表。
 */
class ComplaintDefamationController extends Controller
{
    /**
     * 诽谤类投诉服务实例
     *
     * @var ComplaintDefamationService
     */
    private ComplaintDefamationService $complaintDefamationService;

    /**
     * 构造函数
     *
     * @param ComplaintDefamationService $complaintDefamationService 诽谤类投诉服务
     */
    public function __construct(ComplaintDefamationService $complaintDefamationService)
    {
        $this->complaintDefamationService = $complaintDefamationService;
    }

    /**
     * 获取诽谤类投诉列表
     *
     * 支持按举报网站名称、举报人、举报状态筛选，按ID倒序分页返回。
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $params = $request->only(['site_name', 'human_name', 'report_state', 'pageNum', 'pageSize']);
        $paginator = $this->complaintDefamationService->getList($params);

        return ApiResponse::paginate($paginator, ComplaintDefamationListResource::class);
    }

    /**
     * 获取诽谤类投诉详情
     *
     * 根据ID查询投诉记录详情，不存在时返回错误信息。
     *
     * @param int $id 投诉记录ID
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $item = $this->complaintDefamationService->getById($id);

        return ApiResponse::resource($item, ComplaintDefamationItemResource::class);
    }

    /**
     * 创建诽谤类投诉
     *
     * 使用 CreateComplaintDefamationRequest 验证请求数据，
     * 调用服务层创建投诉记录。
     *
     * @param CreateComplaintDefamationRequest $request
     * @return JsonResponse
     */
    public function store(CreateComplaintDefamationRequest $request): JsonResponse
    {
        $this->complaintDefamationService->create($request->validated());

        return ApiResponse::created();
    }

    /**
     * 更新诽谤类投诉
     *
     * 使用 UpdateComplaintDefamationRequest 验证请求数据，
     * 调用服务层更新投诉记录。
     *
     * @param UpdateComplaintDefamationRequest $request
     * @return JsonResponse
     */
    public function update(UpdateComplaintDefamationRequest $request): JsonResponse
    {
        $data = $request->validated();
        $id = (int)$data['id'];
        unset($data['id']);

        $this->complaintDefamationService->update($id, $data);

        return ApiResponse::updated();
    }

    /**
     * 删除诽谤类投诉
     *
     * 调用服务层执行软删除操作。
     *
     * @param int $id 投诉记录ID
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $this->complaintDefamationService->delete($id);

        return ApiResponse::deleted();
    }

    /**
     * 获取可用发件邮箱列表
     *
     * 从report_email数据表查询并返回可用的邮箱列表。
     *
     * @return JsonResponse
     */
    public function getReportEmails(): JsonResponse
    {
        $reportEmails = $this->complaintDefamationService->getReportEmails();

        return ApiResponse::success(['data' => $reportEmails]);
    }

    /**
     * 发送举报邮件
     *
     * 将邮件发送任务推送到队列异步处理，支持批量发送。
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sendMail(Request $request): JsonResponse
    {
        $params = $request->validate([
            'id' => 'required|integer',
            'recipient_email' => 'required|array',
            'recipient_email.*' => 'required|email',
        ], [
            'id.required' => '举报ID不能为空',
            'id.integer' => '举报ID必须为整数',
            'recipient_email.required' => '收件人邮箱不能为空',
            'recipient_email.array' => '收件人邮箱必须为数组',
            'recipient_email.*.required' => '收件人邮箱不能为空',
            'recipient_email.*.email' => '收件人邮箱格式不正确',
        ]);

        foreach ($params['recipient_email'] as $email) {
            ComplaintDefamationSendMailJob::dispatch([
                'complaint_id' => $params['id'],
                'recipient_email' => $email,
            ]);
        }

        return ApiResponse::success([], '邮件发送任务已加入队列');
    }

    /**
     * 批量审核诽谤类投诉
     *
     * 平台批量审核投诉记录，审核通过后创建者才可以发送邮件请求。
     * 仅允许从"平台审核中"状态进行审核操作。
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function audit(Request $request): JsonResponse
    {
        $params = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer',
            'audit_opinion' => 'required|integer|in:2,3,4',
        ], [
            'ids.required' => '投诉ID列表不能为空',
            'ids.array' => '投诉ID列表必须为数组',
            'ids.min' => '投诉ID列表至少包含一条记录',
            'ids.*.required' => '投诉ID不能为空',
            'ids.*.integer' => '投诉ID必须为整数',
            'audit_opinion.required' => '审核意见不能为空',
            'audit_opinion.integer' => '审核意见必须为整数',
            'audit_opinion.in' => '审核意见值无效，仅支持：2-平台驳回、3-平台审核通过、4-官方审核中',
        ]);

        $auditOpinion = (int)$params['audit_opinion'];

        // 批量审核投诉记录
        foreach ($params['ids'] as $id) {
            $this->complaintDefamationService->audit((int)$id, $auditOpinion);
        }

        return ApiResponse::success([], '批量审核操作成功');
    }
}
