<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CourseCategoryStoreRequest;
use App\Http\Requests\Admin\CourseCategoryUpdateRequest;
use App\Http\Requests\Admin\CourseCategoryStatusRequest;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\Admin\CourseCategoryResource;
use App\Http\Resources\Admin\CourseCategoryListResource;
use App\Http\Resources\Admin\CourseCategorySimpleResource;
use App\Services\Admin\CourseCategoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CourseCategoryController extends Controller
{
    /**
     * @var CourseCategoryService
     */
    protected $categoryService;

    /**
     * CourseCategoryController constructor.
     *
     * @param CourseCategoryService $categoryService
     */
    public function __construct(CourseCategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    /**
     * 分类列表
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function list(Request $request)
    {
        $filters = [
            'categoryName' => $request->input('categoryName'),
            'categoryCode' => $request->input('categoryCode'),
            'status' => $request->input('status'),
            'parentId' => $request->input('parentId'),
            'beginTime' => $request->input('beginTime'),
            'endTime' => $request->input('endTime'),
        ];

        $pageNum = (int) $request->input('pageNum', 1);
        $pageSize = (int) $request->input('pageSize', 10);

        $paginator = $this->categoryService->getList($filters, $pageNum, $pageSize);

        return ApiResponse::paginate($paginator, CourseCategoryListResource::class, '查询成功');
    }

    /**
     * 分类详情
     *
     * @param int $categoryId
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($categoryId)
    {
        $category = $this->categoryService->getDetail((int) $categoryId);

        if (!$category) {
            return ApiResponse::error('分类不存在');
        }

        return ApiResponse::resource($category, CourseCategoryResource::class, '查询成功');
    }

    /**
     * 新增分类
     *
     * @param CourseCategoryStoreRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(CourseCategoryStoreRequest $request)
    {
        try {
            // 检查分类编码是否已存在
            $categoryCode = $request->input('categoryCode');
            if ($categoryCode && $this->categoryService->codeExists($categoryCode)) {
                return ApiResponse::error('分类编码已存在');
            }

            $data = [
                'parentId' => $request->input('parentId', 0),
                'categoryName' => $request->input('categoryName'),
                'categoryCode' => $categoryCode,
                'icon' => $request->input('icon'),
                'cover' => $request->input('cover'),
                'description' => $request->input('description'),
                'sortOrder' => $request->input('sortOrder', 0),
                'status' => $request->input('status', 1),
            ];

            $this->categoryService->create($data);

            return ApiResponse::success([], '新增成功');
        } catch (\Exception $e) {
            Log::error('新增课程分类失败', [
                'action' => 'store',
                'error' => $e->getMessage(),
            ]);
            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 更新分类
     *
     * @param CourseCategoryUpdateRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(CourseCategoryUpdateRequest $request)
    {
        try {
            $categoryId = (int) $request->input('categoryId');

            // 检查分类编码是否已存在（排除自身）
            $categoryCode = $request->input('categoryCode');
            if ($categoryCode && $this->categoryService->codeExists($categoryCode, $categoryId)) {
                return ApiResponse::error('分类编码已存在');
            }

            // 检查是否将自己设为父分类
            $parentId = $request->input('parentId');
            if ($parentId !== null && $parentId == $categoryId) {
                return ApiResponse::error('不能将自己设为父分类');
            }

            $data = [
                'parentId' => $parentId,
                'categoryName' => $request->input('categoryName'),
                'categoryCode' => $categoryCode,
                'icon' => $request->input('icon'),
                'cover' => $request->input('cover'),
                'description' => $request->input('description'),
                'sortOrder' => $request->input('sortOrder'),
                'status' => $request->input('status'),
            ];

            $result = $this->categoryService->update($categoryId, $data);

            if (!$result) {
                return ApiResponse::error('分类不存在');
            }

            return ApiResponse::success([], '修改成功');
        } catch (\Exception $e) {
            Log::error('更新课程分类失败', [
                'action' => 'update',
                'category_id' => $request->input('categoryId'),
                'error' => $e->getMessage(),
            ]);
            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 删除分类（支持批量）
     *
     * @param string $categoryIds 逗号分隔的分类ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($categoryIds)
    {
        try {
            $ids = array_map('intval', explode(',', $categoryIds));

            // 检查是否有子分类
            foreach ($ids as $id) {
                if ($this->categoryService->hasChildren($id)) {
                    return ApiResponse::error('存在子分类，无法删除');
                }
            }

            $deletedCount = $this->categoryService->delete($ids);

            if ($deletedCount > 0) {
                return ApiResponse::success([], '删除成功');
            } else {
                return ApiResponse::error('删除失败，分类不存在');
            }
        } catch (\Exception $e) {
            Log::error('删除课程分类失败', [
                'action' => 'destroy',
                'category_ids' => $categoryIds,
                'error' => $e->getMessage(),
            ]);
            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 修改分类状态
     *
     * @param CourseCategoryStatusRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changeStatus(CourseCategoryStatusRequest $request)
    {
        try {
            $categoryId = (int) $request->input('categoryId');
            $status = (int) $request->input('status');

            $result = $this->categoryService->changeStatus($categoryId, $status);

            if (!$result) {
                return ApiResponse::error('分类不存在');
            }

            return ApiResponse::success([], '修改成功');
        } catch (\Exception $e) {
            Log::error('修改课程分类状态失败', [
                'action' => 'changeStatus',
                'category_id' => $request->input('categoryId'),
                'error' => $e->getMessage(),
            ]);
            return ApiResponse::error('操作失败，请稍后重试');
        }
    }

    /**
     * 下拉选项列表
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function optionselect()
    {
        $options = $this->categoryService->getOptions();

        return ApiResponse::collection($options, CourseCategorySimpleResource::class, '查询成功');
    }

    /**
     * 树形结构列表
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function treeList()
    {
        $list = $this->categoryService->getTreeList();

        return ApiResponse::collection($list, CourseCategoryListResource::class, '查询成功');
    }
}
