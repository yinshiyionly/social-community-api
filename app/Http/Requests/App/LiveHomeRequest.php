<?php

namespace App\Http\Requests\App;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 直播首页请求验证。
 *
 * 职责：
 * 1. 校验 tab/page/pageSize 参数合法性；
 * 2. 统一处理默认值，避免控制器重复兜底；
 * 3. 规范 tab 大小写，保证服务层仅处理固定枚举值。
 */
class LiveHomeRequest extends FormRequest
{
    const TAB_UPCOMING = 'upcoming';
    const TAB_REPLAY = 'replay';
    const DEFAULT_PAGE = 1;
    const DEFAULT_PAGE_SIZE = 20;
    const MAX_PAGE_SIZE = 100;

    /**
     * 所有人可访问，登录态由可选鉴权中间件控制。
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
            'tab' => 'required|string|in:' . self::TAB_UPCOMING . ',' . self::TAB_REPLAY,
            'page' => 'nullable|integer|min:1',
            'pageSize' => 'nullable|integer|min:1|max:' . self::MAX_PAGE_SIZE,
        ];
    }

    /**
     * 统一错误提示，避免前端拿到暴露实现细节的校验信息。
     *
     * @return array<string, string>
     */
    public function messages()
    {
        return [
            'tab.required' => 'tab 参数错误',
            'tab.string' => 'tab 参数错误',
            'tab.in' => 'tab 参数错误',
            'page.integer' => 'page 参数错误',
            'page.min' => 'page 参数错误',
            'pageSize.integer' => 'pageSize 参数错误',
            'pageSize.min' => 'pageSize 参数错误',
            'pageSize.max' => 'pageSize 参数错误',
        ];
    }

    /**
     * 在校验前统一 tab 格式，避免大小写差异导致误判。
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $tab = $this->input('tab');
        if (!is_string($tab)) {
            return;
        }

        $this->merge([
            'tab' => strtolower(trim($tab)),
        ]);
    }

    /**
     * 获取 tab 枚举值。
     *
     * @return string
     */
    public function getTab(): string
    {
        return (string)$this->input('tab', self::TAB_UPCOMING);
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
     * 获取每页条数。
     *
     * @return int
     */
    public function getPageSize(): int
    {
        return (int)$this->input('pageSize', self::DEFAULT_PAGE_SIZE);
    }
}
