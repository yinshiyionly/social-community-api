<?php

namespace App\Services\Admin;

use App\Models\App\AppCourseBase;
use App\Models\App\AppCourseCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
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
        $query = AppCourseCategory::query()
            ->select([
                'category_id', 'parent_id', 'category_name',
                'icon', 'sort_order', 'status', 'created_at',
            ]);

        // 分类名称搜索
        if (!empty($filters['categoryName'])) {
            $query->where('category_name', 'like', '%' . $filters['categoryName'] . '%');
        }

        // 状态筛选
        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        // 排序
        $query->orderBy('sort_order')->orderByDesc('category_id');

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
            'parent_id' => 0,
            'category_name' => $data['categoryName'],
            'icon' => $data['icon'],
            'status' => $data['status'],
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
        $category = AppCourseCategory::query()
            ->where('category_id', $categoryId)
            ->first();

        if (!$category) {
            return false;
        }

        $updateData = [];

        if (isset($data['categoryName'])) {
            $updateData['category_name'] = $data['categoryName'];
        }
        if (isset($data['icon'])) {
            $updateData['icon'] = $data['icon'];
        }
        if (isset($data['status'])) {
            $updateData['status'] = $data['status'];
        }

        return $category->update($updateData);
    }


    /**
     * 删除分类-不支持批量删除
     * 软删除
     *
     * @param int $categoryId
     * @return int 删除数量
     */
    public function delete(int $categoryId): int
    {
        return AppCourseCategory::query()
            ->where('category_id', $categoryId)
            ->whereNull('deleted_at')
            ->update([
                'deleted_at' => now(),
                'deleted_by' => $this->getCurrentOperatorId(),
            ]);
    }

    /**
     * 获取当前操作人ID
     *
     * @return int|null
     */
    protected function getCurrentOperatorId(): ?int
    {
        return 0;
        $request = request();

        // System 端用户（后台管理员）
        if ($request && $request->attributes->has('system_user_id')) {
            return (int)$request->attributes->get('system_user_id');
        }

        // Admin guard 登录用户
        if (Auth::guard('admin')->check()) {
            return (int)Auth::guard('admin')->id();
        }

        return null;
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
     * 批量更新分类排序
     *
     * @param array $categoryData [['categoryId' => 1, 'courseSort' => 999], ...]
     * @return bool
     */
    public function batchUpdateSort(array $categoryData): bool
    {
        return DB::transaction(function () use ($categoryData) {
            foreach ($categoryData as $item) {
                AppCourseCategory::query()
                    ->where('category_id', $item['categoryId'])
                    ->whereNull('deleted_at')
                    ->update(['sort_order' => $item['categorySort']]);
            }

            return true;
        });
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
            ->select([
                'category_id', 'parent_id', 'category_name',
                'icon', 'sort_order', 'status', 'created_at',
            ])
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

    /**
     * 检查分类下是否有正常课程（上架）
     *
     * @param int $categoryId
     * @return bool
     */
    public function hasNormalCourses(int $categoryId): bool
    {
        return AppCourseBase::query()
            ->where('category_id', $categoryId)
            ->where('status', AppCourseBase::STATUS_ONLINE)
            ->exists();
    }
}
