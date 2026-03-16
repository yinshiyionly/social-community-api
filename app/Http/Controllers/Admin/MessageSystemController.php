<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MessageSystemListRequest;
use App\Http\Requests\Admin\MessageSystemSendRequest;
use App\Http\Resources\Admin\MessageSystemListResource;
use App\Http\Resources\Admin\OfficialMemberOptionResource;
use App\Http\Resources\ApiResponse;
use App\Services\Admin\MessageSystemService;
use Illuminate\Support\Facades\Log;

/**
 * 后台系统消息控制器。
 *
 * 接口职责：
 * 1. 提供系统消息发送入口（广播/定向）；
 * 2. 提供系统消息后台列表查询能力；
 * 3. 提供官方发送者下拉选项，支持后台发送页选择 senderId。
 */
class MessageSystemController extends Controller
{
    /**
     * @var MessageSystemService
     */
    protected $messageSystemService;

    /**
     * @param MessageSystemService $messageSystemService
     */
    public function __construct(MessageSystemService $messageSystemService)
    {
        $this->messageSystemService = $messageSystemService;
    }

    /**
     * 官方发送者下拉选项。
     *
     * 接口用途：
     * - 为后台系统消息发送页提供 senderId 候选项；
     * - 仅返回官方且正常状态账号，避免误选普通会员或禁用账号。
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function senderOptions()
    {
        try {
            $options = $this->messageSystemService->getSenderOptions();

            return ApiResponse::collection($options, OfficialMemberOptionResource::class, '查询成功');
        } catch (\Exception $e) {
            Log::error('查询系统消息发送者下拉失败', [
                'action' => 'senderOptions',
                'error' => $e->getMessage(),
            ]);

            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 发送系统消息。
     *
     * 接口用途：
     * - 后台运营以官方账号身份发送系统消息；
     * - 支持按 memberIds 定向发送或不传 memberIds 的全员广播。
     *
     * 关键输入：
     * - senderId：发送者官方会员ID；
     * - memberIds：可选，最多 100 个接收者；
     * - title/content/linkType/linkUrl/coverUrl：消息内容与跳转信息。
     *
     * 关键输出：
     * - 返回 sentCount，表示实际写入消息条数；
     * - 当 memberIds 全部无效时返回成功且 sentCount=0（不降级为广播）。
     *
     * @param MessageSystemSendRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function send(MessageSystemSendRequest $request)
    {
        $payload = [
            'sender_id' => (int) $request->input('senderId'),
            'title' => (string) $request->input('title'),
            'content' => (string) $request->input('content'),
            'cover_url' => $request->input('coverUrl'),
            'link_type' => $request->input('linkType'),
            'link_url' => $request->input('linkUrl'),
        ];

        // 仅在请求显式传入 memberIds 时按定向模式处理，避免误把空参数当作定向发送。
        if ($request->exists('memberIds')) {
            $payload['member_ids'] = $request->input('memberIds');
        }

        try {
            $result = $this->messageSystemService->send($payload);

            return ApiResponse::success([
                'data' => [
                    'sentCount' => $result['sentCount'],
                ],
            ], '发送成功');
        } catch (\Exception $e) {
            Log::error('发送系统消息失败', [
                'action' => 'send',
                'payload' => $payload,
                'error' => $e->getMessage(),
            ]);

            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 系统消息列表（分页）。
     *
     * 接口用途：
     * - 提供后台系统消息列表查询；
     * - 默认展示全员广播消息，支持按广播标识、会员ID、发送时间、已读状态筛选。
     *
     * 关键输入：
     * - 分页参数：pageNum、pageSize；
     * - 筛选参数：isBroadcast、memberId、beginTime、endTime、isRead。
     *
     * 关键输出：
     * - 返回 ApiResponse::paginate 结构（code、msg、total、rows）；
     * - rows 中包含消息核心字段与发送者/接收者信息。
     *
     * @param MessageSystemListRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function list(MessageSystemListRequest $request)
    {
        $filters = [
            'isBroadcast' => $request->input('isBroadcast', 1),
            'memberId' => $request->input('memberId'),
            'beginTime' => $request->input('beginTime'),
            'endTime' => $request->input('endTime'),
            'isRead' => $request->input('isRead'),
        ];

        $pageNum = (int) $request->input('pageNum', 1);
        $pageSize = (int) $request->input('pageSize', 10);

        try {
            $paginator = $this->messageSystemService->getList($filters, $pageNum, $pageSize);

            return ApiResponse::paginate($paginator, MessageSystemListResource::class, '查询成功');
        } catch (\Exception $e) {
            Log::error('查询系统消息列表失败', [
                'action' => 'list',
                'filters' => $filters,
                'page_num' => $pageNum,
                'page_size' => $pageSize,
                'error' => $e->getMessage(),
            ]);

            return ApiResponse::error('操作失败，请稍后重试');
        }
    }
}
