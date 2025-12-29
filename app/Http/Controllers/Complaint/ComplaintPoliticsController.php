<?php

declare(strict_types=1);

namespace App\Http\Controllers\Complaint;

use App\Http\Controllers\Controller;
use App\Http\Requests\Complaint\ComplaintPolitics\CreateComplaintPoliticsRequest;
use App\Http\Requests\Complaint\ComplaintPolitics\UpdateComplaintPoliticsRequest;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\Complaint\ComplaintPolitics\ComplaintPoliticsItemResource;
use App\Http\Resources\Complaint\ComplaintPolitics\ComplaintPoliticsListResource;
use App\Jobs\Complaint\ComplaintPoliticsSendMailJob;
use App\Services\Complaint\ComplaintPoliticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 政治类投诉控制器
 *
 * 处理政治类投诉相关的HTTP请求，包括：
 * - CRUD操作（列表、详情、创建、更新、删除）
 * - 枚举值获取（危害小类、被举报平台、APP定位、账号平台、账号性质）
 * - 发件邮箱列表获取
 */
class ComplaintPoliticsController extends Controller
{
    /**
     * 政治类投诉服务实例
     *
     * @var ComplaintPoliticsService
     */
    private ComplaintPoliticsService $complaintPoliticsService;

    /**
     * 构造函数
     *
     * @param ComplaintPoliticsService $complaintPoliticsService 政治类投诉服务
     */
    public function __construct(ComplaintPoliticsService $complaintPoliticsService)
    {
        $this->complaintPoliticsService = $complaintPoliticsService;
    }

    /**
     * 获取政治类投诉列表
     *
     * 支持按 site_name/app_name/account_name/human_name/report_state 筛选，
     * 按ID倒序分页返回。
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $params = $request->only([
            'site_name',
            'app_name',
            'account_name',
            'human_name',
            'report_state',
            'pageNum',
            'pageSize',
        ]);
        $paginator = $this->complaintPoliticsService->getList($params);

        return ApiResponse::paginate($paginator, ComplaintPoliticsListResource::class);
    }

    /**
     * 获取政治类投诉详情
     *
     * 根据ID查询投诉记录详情，不存在时返回错误信息。
     *
     * @param int $id 投诉记录ID
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $item = $this->complaintPoliticsService->getById($id);

        return ApiResponse::resource($item, ComplaintPoliticsItemResource::class);
    }

    /**
     * 创建政治类投诉
     *
     * 使用 CreateComplaintPoliticsRequest 验证请求数据，
     * 调用服务层创建投诉记录。
     *
     * @param CreateComplaintPoliticsRequest $request
     * @return JsonResponse
     */
    public function store(CreateComplaintPoliticsRequest $request): JsonResponse
    {
        $this->complaintPoliticsService->create($request->validated());

        return ApiResponse::created();
    }

    /**
     * 更新政治类投诉
     *
     * 使用 UpdateComplaintPoliticsRequest 验证请求数据，
     * 调用服务层更新投诉记录。
     *
     * @param UpdateComplaintPoliticsRequest $request
     * @return JsonResponse
     */
    public function update(UpdateComplaintPoliticsRequest $request): JsonResponse
    {
        $data = $request->validated();
        $id = (int)$data['id'];
        unset($data['id']);

        $this->complaintPoliticsService->update($id, $data);

        return ApiResponse::updated();
    }

    /**
     * 删除政治类投诉
     *
     * 调用服务层执行软删除操作。
     *
     * @param int $id 投诉记录ID
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $this->complaintPoliticsService->delete($id);

        return ApiResponse::deleted();
    }

    /**
     * 获取危害小类枚举列表
     *
     * 返回政治类举报的8种危害小类枚举值列表。
     *
     * @return JsonResponse
     */
    public function getReportSubTypes(): JsonResponse
    {
        $reportSubTypes = $this->complaintPoliticsService->getReportSubTypes();

        return ApiResponse::success(['data' => $reportSubTypes]);
    }

    /**
     * 获取被举报平台枚举列表
     *
     * 返回被举报平台枚举值列表（网站网页/APP/网络账号）。
     *
     * @return JsonResponse
     */
    public function getReportPlatforms(): JsonResponse
    {
        $reportPlatforms = $this->complaintPoliticsService->getReportPlatforms();

        return ApiResponse::success(['data' => $reportPlatforms]);
    }

    /**
     * 获取APP定位枚举列表
     *
     * 返回APP定位枚举值列表（有害信息链接/APP官方网址/APP下载地址）。
     *
     * @return JsonResponse
     */
    public function getAppLocations(): JsonResponse
    {
        $appLocations = $this->complaintPoliticsService->getAppLocations();

        return ApiResponse::success(['data' => $appLocations]);
    }

    /**
     * 获取账号平台枚举列表
     *
     * 返回账号平台枚举值列表（微信/QQ/微博/贴吧/博客/直播平台/论坛社区/网盘/音频/其他）。
     *
     * @return JsonResponse
     */
    public function getAccountPlatforms(): JsonResponse
    {
        $accountPlatforms = $this->complaintPoliticsService->getAccountPlatforms();

        return ApiResponse::success(['data' => $accountPlatforms]);
    }

    /**
     * 根据账号平台获取账号性质枚举列表
     *
     * 根据不同的账号平台返回对应的账号性质选项：
     * - 微信：个人/公众/群组
     * - QQ：个人/群组
     * - 微博：认证/非认证
     * - 其他平台：空数组
     *
     * @param string $platform 账号平台
     * @return JsonResponse
     */
    public function getAccountNatures(string $platform): JsonResponse
    {
        $accountNatures = $this->complaintPoliticsService->getAccountNatures($platform);

        return ApiResponse::success(['data' => $accountNatures]);
    }

    /**
     * 获取可用发件邮箱列表
     *
     * 从 report_email 数据表查询并返回可用的邮箱列表。
     *
     * @return JsonResponse
     */
    public function getReportEmails(): JsonResponse
    {
        $reportEmails = $this->complaintPoliticsService->getReportEmails();

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
            ComplaintPoliticsSendMailJob::dispatch([
                'complaint_id' => $params['id'],
                'recipient_email' => $email,
            ]);
        }

        return ApiResponse::success([], '邮件发送任务已加入队列');
    }

    /**
     * 批量审核政治类投诉
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
            $this->complaintPoliticsService->audit((int)$id, $auditOpinion);
        }

        return ApiResponse::success([], '批量审核操作成功');
    }
}
