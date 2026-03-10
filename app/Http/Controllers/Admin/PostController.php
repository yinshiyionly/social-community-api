<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PostAuditRequest;
use App\Http\Resources\Admin\PostListResource;
use App\Http\Resources\Admin\PostResource;
use App\Http\Resources\ApiResponse;
use App\Services\Admin\PostService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * 后台帖子管理控制器。
 *
 * 接口范围：
 * 1. 帖子列表查询；
 * 2. 帖子详情查询；
 * 3. 帖子审核。
 */
class PostController extends Controller
{
    /**
     * @var PostService
     */
    protected $postService;

    /**
     * @param PostService $postService
     */
    public function __construct(PostService $postService)
    {
        $this->postService = $postService;
    }

    /**
     * 帖子列表（分页）。
     *
     * 接口用途：
     * - 后台帖子管理页查询帖子。
     *
     * 关键输入：
     * - 筛选参数：postId、memberId、postType、status、visible、isTop、beginTime、endTime；
     * - 分页参数：pageNum、pageSize。
     *
     * 关键输出：
     * - 返回 ApiResponse::paginate 结构（code、msg、total、rows）。
     *
     * 失败分支：
     * - 参数不合法时返回统一“参数错误”，避免下沉到数据库层再报错。
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function list(Request $request)
    {
        $payload = [
            'postId' => $this->nullableInput($request->input('postId')),
            'memberId' => $this->nullableInput($request->input('memberId')),
            'postType' => $this->nullableInput($request->input('postType')),
            'status' => $this->nullableInput($request->input('status')),
            'visible' => $this->nullableInput($request->input('visible')),
            'isTop' => $this->nullableInput($request->input('isTop')),
            'beginTime' => $this->nullableInput($request->input('beginTime')),
            'endTime' => $this->nullableInput($request->input('endTime')),
            'pageNum' => (int) $request->input('pageNum', 1),
            'pageSize' => (int) $request->input('pageSize', 10),
        ];

        $validator = Validator::make($payload, [
            'postId' => 'nullable|integer|min:1',
            'memberId' => 'nullable|integer|min:1',
            'postType' => 'nullable|integer|in:1,2,3',
            'status' => 'nullable|integer|in:0,1,2',
            'visible' => 'nullable|integer|in:0,1',
            'isTop' => 'nullable|integer|in:0,1',
            'beginTime' => 'nullable|date',
            'endTime' => 'nullable|date',
            'pageNum' => 'required|integer|min:1',
            'pageSize' => 'required|integer|min:1|max:100',
        ]);

        // 参数校验失败时直接返回，避免无效条件触发全表扫描或异常 SQL。
        if ($validator->fails()) {
            return ApiResponse::error('参数错误');
        }

        // beginTime 与 endTime 都存在时，明确限制结束时间不能早于开始时间。
        if (!empty($payload['beginTime']) && !empty($payload['endTime'])
            && strtotime((string) $payload['endTime']) < strtotime((string) $payload['beginTime'])) {
            return ApiResponse::error('参数错误');
        }

        $paginator = $this->postService->getList([
            'postId' => $payload['postId'],
            'memberId' => $payload['memberId'],
            'postType' => $payload['postType'],
            'status' => $payload['status'],
            'visible' => $payload['visible'],
            'isTop' => $payload['isTop'],
            'beginTime' => $payload['beginTime'],
            'endTime' => $payload['endTime'],
        ], $payload['pageNum'], $payload['pageSize']);

        return ApiResponse::paginate($paginator, PostListResource::class, '查询成功');
    }

    /**
     * 帖子详情。
     *
     * 接口用途：
     * - 后台帖子详情页加载帖子完整信息（基础字段 + 统计字段）。
     *
     * 关键输入：
     * - postId 路径参数（数字）。
     *
     * 关键输出：
     * - 返回 ApiResponse::resource 结构（code、msg、data）。
     *
     * 失败分支：
     * - 当帖子不存在或已软删时返回“帖子不存在”。
     *
     * @param int $postId
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(int $postId)
    {
        $post = $this->postService->getDetail($postId);

        if (!$post) {
            return ApiResponse::error('帖子不存在');
        }

        return ApiResponse::resource($post, PostResource::class, '查询成功');
    }

    /**
     * 帖子审核（通过/拒绝）。
     *
     * 接口用途：
     * - 后台审核帖子内容，更新帖子审核状态。
     *
     * 关键输入：
     * - postId：帖子ID；
     * - status：审核结果（1=已通过，2=已拒绝）。
     *
     * 关键输出：
     * - 返回 ApiResponse::success/error 结构。
     *
     * 失败分支：
     * - 帖子不存在；
     * - 帖子非待审核状态，不允许重复审核。
     *
     * @param PostAuditRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function audit(PostAuditRequest $request)
    {
        try {
            $postId = (int) $request->input('postId');
            $status = (int) $request->input('status');

            $result = $this->postService->audit($postId, $status);

            if ($result === PostService::AUDIT_RESULT_NOT_FOUND) {
                return ApiResponse::error('帖子不存在');
            }

            if ($result === PostService::AUDIT_RESULT_ALREADY_AUDITED) {
                return ApiResponse::error('帖子已审核，不能重复审核');
            }

            return ApiResponse::success([], '审核成功');
        } catch (\Exception $e) {
            Log::error('帖子审核失败', [
                'action' => 'audit',
                'post_id' => $request->input('postId'),
                'status' => $request->input('status'),
                'error' => $e->getMessage(),
            ]);

            // 记录服务端详细日志，接口层统一返回通用错误，避免暴露内部异常细节。
            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 将空字符串统一转为 null，保持筛选参数语义一致。
     *
     * @param mixed $value
     * @return mixed
     */
    protected function nullableInput($value)
    {
        return $value === '' ? null : $value;
    }
}
