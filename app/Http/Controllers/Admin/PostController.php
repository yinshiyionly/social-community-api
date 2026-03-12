<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PostAuditRequest;
use App\Http\Requests\Admin\PostStoreArticleRequest;
use App\Http\Requests\Admin\PostStoreImageTextRequest;
use App\Http\Requests\Admin\PostStoreVideoRequest;
use App\Http\Resources\Admin\OfficialMemberOptionResource;
use App\Http\Resources\Admin\PostListResource;
use App\Http\Resources\Admin\PostResource;
use App\Http\Resources\ApiResponse;
use App\Models\App\AppPostBase;
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
 * 3. 帖子审核；
 * 4. 后台发帖（图文/视频/文章）；
 * 5. 后台发帖官方账号下拉查询；
 * 6. 帖子管理常量选项查询。
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
     * 帖子模块常量选项。
     *
     * 接口用途：
     * - 为帖子管理筛选条件与表单控件提供统一枚举数据；
     * - 返回最小可用选项集，避免前端硬编码业务枚举值。
     *
     * 字段口径：
     * - status 按当前后台接口行为返回 0/1/2（待审核/已通过/已拒绝）；
     * - 不依赖模型中存在歧义的状态常量，避免枚举值漂移。
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function constants()
    {
        $data = [
            'postTypeOptions' => [
                ['label' => '图文', 'value' => AppPostBase::POST_TYPE_IMAGE_TEXT],
                ['label' => '视频', 'value' => AppPostBase::POST_TYPE_VIDEO],
                ['label' => '文章', 'value' => AppPostBase::POST_TYPE_ARTICLE],
            ],
            'visibleOptions' => [
                ['label' => '私密', 'value' => AppPostBase::VISIBLE_PRIVATE],
                ['label' => '公开', 'value' => AppPostBase::VISIBLE_PUBLIC],
            ],
            'statusOptions' => [
                ['label' => '待审核', 'value' => 0],
                ['label' => '已通过', 'value' => 1],
                ['label' => '已拒绝', 'value' => 2],
            ],
        ];

        return ApiResponse::success(['data' => $data], '查询成功');
    }

    /**
     * 官方发帖账号下拉选项。
     *
     * 接口用途：
     * - 为后台发帖页面提供 member_id 下拉候选项。
     *
     * 关键输出：
     * - 返回 ApiResponse::collection 结构；
     * - 每项包含 memberId、nickname、avatar、officialLabel。
     *
     * 失败分支：
     * - 查询异常时记录日志并返回通用错误，避免暴露内部异常细节。
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function officialMemberOptionselect()
    {
        try {
            $options = $this->postService->getOfficialMemberOptions();

            return ApiResponse::collection($options, OfficialMemberOptionResource::class, '查询成功');
        } catch (\Exception $e) {
            Log::error('查询官方发帖账号下拉失败', [
                'action' => 'officialMemberOptionselect',
                'error' => $e->getMessage(),
            ]);

            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 后台发布图文帖子。
     *
     * 接口用途：
     * - 后台运营以官方账号身份发布图文帖子。
     *
     * 关键输入：
     * - memberId：后台下拉选中的官方账号ID；
     * - 其余参数遵循 App 图文发帖规则（content/images/topics 等）。
     *
     * 关键输出：
     * - 返回 ApiResponse::success 结构，data 内包含 postId。
     *
     * 失败分支：
     * - 服务异常时记录日志并返回通用错误，避免泄露内部实现细节。
     *
     * @param PostStoreImageTextRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeImageText(PostStoreImageTextRequest $request)
    {
        try {
            $payload = $request->validatedWithDefaults();
            $memberId = (int) ($payload['memberId'] ?? 0);
            unset($payload['memberId']);

            $postId = $this->postService->createByAdmin($memberId, $payload);

            return ApiResponse::success([
                'data' => ['postId' => $postId],
            ], '发布成功');
        } catch (\Exception $e) {
            Log::error('后台发布图文帖子失败', [
                'action' => 'storeImageText',
                'member_id' => $request->input('memberId'),
                'error' => $e->getMessage(),
            ]);

            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 后台发布视频帖子。
     *
     * 接口用途：
     * - 后台运营以官方账号身份发布视频帖子。
     *
     * 关键输入：
     * - memberId：后台下拉选中的官方账号ID；
     * - 其余参数遵循 App 视频发帖规则（videoUrl/coverUrl/content/topics 等）。
     *
     * 关键输出：
     * - 返回 ApiResponse::success 结构，data 内包含 postId。
     *
     * 失败分支：
     * - 服务异常时记录日志并返回通用错误，避免泄露内部异常细节。
     *
     * @param PostStoreVideoRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeVideo(PostStoreVideoRequest $request)
    {
        try {
            $payload = $request->validatedWithDefaults();
            $memberId = (int) ($payload['memberId'] ?? 0);
            unset($payload['memberId']);

            $postId = $this->postService->createByAdmin($memberId, $payload);

            return ApiResponse::success([
                'data' => ['postId' => $postId],
            ], '发布成功');
        } catch (\Exception $e) {
            Log::error('后台发布视频帖子失败', [
                'action' => 'storeVideo',
                'member_id' => $request->input('memberId'),
                'error' => $e->getMessage(),
            ]);

            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 后台发布文章帖子。
     *
     * 接口用途：
     * - 后台运营以官方账号身份发布文章帖子。
     *
     * 关键输入：
     * - memberId：后台下拉选中的官方账号ID；
     * - 其余参数遵循 App 文章发帖规则（title/content/cover 等）。
     *
     * 关键输出：
     * - 返回 ApiResponse::success 结构，data 内包含 postId。
     *
     * 失败分支：
     * - 服务异常时记录日志并返回通用错误，避免泄露内部异常细节。
     *
     * @param PostStoreArticleRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeArticle(PostStoreArticleRequest $request)
    {
        try {
            $payload = $request->validatedWithDefaults();
            $memberId = (int) ($payload['memberId'] ?? 0);
            unset($payload['memberId']);

            $postId = $this->postService->createByAdmin($memberId, $payload);

            return ApiResponse::success([
                'data' => ['postId' => $postId],
            ], '发布成功');
        } catch (\Exception $e) {
            Log::error('后台发布文章帖子失败', [
                'action' => 'storeArticle',
                'member_id' => $request->input('memberId'),
                'error' => $e->getMessage(),
            ]);

            return ApiResponse::error('操作失败，请稍后重试');
        }
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
