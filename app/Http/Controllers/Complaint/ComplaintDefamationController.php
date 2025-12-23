<?php

declare(strict_types=1);

namespace App\Http\Controllers\Complaint;

use App\Http\Controllers\Controller;
use App\Http\Requests\Complaint\ComplaintDefamation\CreateComplaintDefamationRequest;
use App\Http\Requests\Complaint\ComplaintDefamation\UpdateComplaintDefamationRequest;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\Complaint\ComplaintDefamation\ComplaintDefamationItemResource;
use App\Http\Resources\Complaint\ComplaintDefamation\ComplaintDefamationListResource;
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
}
