<?php

namespace App\Services\Admin;

use App\Models\App\AppCourseCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CourseCategoryService
{
    /**
     * 获取分类列表（分页）
     *
     * @param array $filters 筛选条件
     * @param int $pageNum 页码
     * @param int $pageSize 每页数量
     * @return LengthAwarePaginator
     */
    public function getList(array $filters, int $pageNum = 1, int $pageSize = 10): LengthAwarePaginator
    {
        $query = AppCourseCategory::query();

        // 分类名称搜索
        if (!empty($filters['categoryName'])) {
            $query->where('category_name', 'like', '%' . $filters['categoryName'] . '%');
        }

        // 分类编码搜索
        if (!empty($filters['categoryCode'])) {
            $query->where('category_code', 'like', '%' . $filters['categoryCode'] . '%');
        }

        // 状态筛选
        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        // 父分类筛选
        if (isset($filters['parentId']) && $filters['parentId'] !== '') {
            $query->where('parent_id', $filters['parentId']);
        }

        // 时间范围筛选
        if (!empty($filters['beginTime'])) {
            $query->where('create_time', '>=', $filters['beginTime']);
        }
        if (!empty($filters['endTime'])) {
            $query->where('create_time', '<=', $filters['endTime']);
        }

        // 排序
        $query->orderByDesc('sort_order')->orderByDesc('category_id');

        return $query->paginate($pageSize, ['*'], 'pageNum', $pageNum);
    }

    /**
     * 获取分类详情
     *
     * @param int $categoryId
     * @return AppCourseCategory|null
     */
    public function getDetail(int $categoryId): ?AppCourseCategory
    {
        return AppCourseCategory::query()
            ->with('parent:category_id,category_name')
            ->where('category_id', $categoryId)
            ->first();
    }

    /**
     * 创建分类
     *
     * @param array $data
     * @return AppCourseCategory
     */
    public function create(array $data): AppCourseCategory
    {
        return AppCourseCategory::create([
            'parent_id' => $data['parentId'] ?? 0,
            'category_name' => $data['categoryName'],
            'category_code' => $data['categoryCode'] ?? '',
            'icon' => $data['icon'] ?? null,
            'cover' => $data['cover'] ?? null,
            'description' => $data['description'] ?? null,
            'sort_order' => $data['sortOrder'] ?? 0,
            'status' => $data['status'] ?? AppCourseCategory::STATUS_ENABLED,
            'create_by' => $data['createBy'] ?? null,
        ]);
    }

    /**
     * 更新分类
     *
     * @param int $categoryId
     * @param array $data
     * @return bool
     */
    public function update(int $categoryId, array $data): bool
    {
        $category = AppCourseCategory::query()->where('category_id', $categoryId)->first();

        if (!$category) {
            return false;
        }

        $updateData = [];

        if (isset($data['parentId'])) {
            $updateData['parent_id'] = $data['parentId'];
        }
        if (isset($data['categoryName'])) {
            $updateData['category_name'] = $data['categoryName'];
        }
        if (array_key_exists('categoryCode', $data)) {
            $updateData['category_code'] = $data['categoryCode'] ?? '';
        }
        if (array_key_exists('icon', $data)) {
            $updateData['icon'] = $data['icon'];
        }
        if (array_key_exists('cover', $data)) {
            $updateData['cover'] = $data['cover'];
        }
        if (array_key_exists('description', $data)) {
            $updateData['description'] = $data['description'];
        }
        if (isset($data['sortOrder'])) {
            $updateData['sort_order'] = $data['sortOrder'];
        }
        if (isset($data['status'])) {
            $updateData['status'] = $data['status'];
        }
        if (isset($data['updateBy'])) {
            $updateData['update_by'] = $data['updateBy'];
        }

        return $category->update($updateData);
    }

    /**
     * 删除分类（支持批量，软删除）
     *
     * @param array $categoryIds
     * @return int 删除数量
     */
    public function delete(array $categoryIds): int
    {
        return AppCourseCategory::query()
            ->whereIn('category_id', $categoryIds)
            ->update([
                'del_flag' => AppCourseCategory::DEL_FLAG_DELETED,
                'update_time' => now(),
            ]);
    }

    /**
     * 修改分类状态
     *
     * @param int $categoryId
     * @param int $status
     * @return bool
     */
    public function changeStatus(int $categoryId, int $status): bool
    {
        return AppCourseCategory::query()
                ->where('category_id', $categoryId)
                ->update(['status' => $status]) > 0;
    }

    /**
     * 获取下拉选项列表（只返回启用状态的分类）
     *
     * @return Collection
     */
    public function getOptions(): Collection
    {
        return AppCourseCategory::query()
            ->select(['category_id', 'parent_id', 'category_name'])
            ->where('status', AppCourseCategory::STATUS_ENABLED)
            ->orderByDesc('sort_order')
            ->orderByDesc('category_id')
            ->get();
    }

    /**
     * 获取树形结构列表
     *
     * @return Collection
     */
    public function getTreeList(): Collection
    {
        return AppCourseCategory::query()
            ->select(['category_id', 'parent_id', 'category_name', 'category_code', 'icon', 'sort_order', 'status', 'create_time'])
            ->orderByDesc('sort_order')
            ->orderByDesc('category_id')
            ->get();
    }

    /**
     * 检查分类是否存在
     *
     * @param int $categoryId
     * @return bool
     */
    public function exists(int $categoryId): bool
    {
        return AppCourseCategory::query()
            ->where('category_id', $categoryId)
            ->exists();
    }

    /**
     * 检查分类编码是否已存在
     *
     * @param string $categoryCode
     * @param int|null $excludeId 排除的分类ID（用于更新时）
     * @return bool
     */
    public function codeExists(string $categoryCode, ?int $excludeId = null): bool
    {
        $query = AppCourseCategory::query()->where('category_code', $categoryCode);

        if ($excludeId) {
            $query->where('category_id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * 检查分类下是否有子分类
     *
     * @param int $categoryId
     * @return bool
     */
    public function hasChildren(int $categoryId): bool
    {
        return AppCourseCategory::query()
            ->where('parent_id', $categoryId)
            ->exists();
    }
}
