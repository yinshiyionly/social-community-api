<?php

namespace App\Http\Requests\App;

use Illuminate\Foundation\Http\FormRequest;

/**
 * App 端课程订单列表请求验证。
 *
 * 职责：
 * 1. 校验 page/pageSize/status 参数合法性；
 * 2. 统一 status 参数格式，避免大小写与空格导致筛选失效；
 * 3. 提供分页与筛选读取方法，减少控制器重复处理。
 */
class CourseOrderListRequest extends FormRequest
{
    const DEFAULT_PAGE = 1;
    const DEFAULT_PAGE_SIZE = 20;

    /**
     * 订单列表需要登录，具体鉴权由路由中间件 app.auth 控制。
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * 请求参数规则。
     *
     * @return array<string, string>
     */
    public function rules()
    {
        return [
            'page' => 'required|integer|min:1',
            'pageSize' => 'required|integer|min:1|max:50',
            'status' => 'nullable|string|in:unpaid,paid,closed,refunded',
        ];
    }

    /**
     * 参数校验失败提示。
     *
     * @return array<string, string>
     */
    public function messages()
    {
        return [
            'page.required' => 'page 参数不能为空',
            'page.integer' => 'page 参数错误',
            'page.min' => 'page 参数错误',
            'pageSize.required' => 'pageSize 参数不能为空',
            'pageSize.integer' => 'pageSize 参数错误',
            'pageSize.min' => 'pageSize 参数错误',
            'pageSize.max' => 'pageSize 参数错误',
            'status.string' => 'status 参数错误',
            'status.in' => 'status 参数错误',
        ];
    }

    /**
     * 在校验前统一处理 status，确保服务层仅接收规范枚举值。
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $status = $this->input('status');

        if (!is_string($status)) {
            return;
        }

        $normalizedStatus = strtolower(trim($status));

        // 空字符串按“未传筛选条件”处理，避免前端透传空值触发 in 校验失败。
        if ($normalizedStatus === '') {
            $this->merge([
                'status' => null,
            ]);

            return;
        }

        $this->merge([
            'status' => $normalizedStatus,
        ]);
    }

    /**
     * 获取页码。
     *
     * @return int
     */
    public function getPage(): int
    {
        return (int)$this->input('page', self::DEFAULT_PAGE);
    }

    /**
     * 获取每页数量。
     *
     * @return int
     */
    public function getPageSize(): int
    {
        return (int)$this->input('pageSize', self::DEFAULT_PAGE_SIZE);
    }

    /**
     * 获取状态筛选值。
     *
     * @return string|null
     */
    public function getStatus(): ?string
    {
        $status = $this->input('status');

        if (!is_string($status) || trim($status) === '') {
            return null;
        }

        return strtolower(trim($status));
    }
}
