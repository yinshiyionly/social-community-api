<?php

namespace App\Http\Requests\App;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 搜索全部请求验证
 */
class SearchAllRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'keyword' => 'required|string|max:100',
            'page' => 'sometimes|integer|min:1',
            'pageSize' => 'sometimes|integer|min:1|max:50',
        ];
    }

    public function messages()
    {
        return [
            'keyword.required' => '搜索关键词不能为空',
            'keyword.string' => '搜索关键词格式错误',
            'keyword.max' => '搜索关键词不能超过100个字符',
            'page.integer' => '页码必须是整数',
            'page.min' => '页码最小为1',
            'pageSize.integer' => '每页数量必须是整数',
            'pageSize.min' => '每页数量最小为1',
            'pageSize.max' => '每页数量最大为50',
        ];
    }

    /**
     * 获取处理后的关键词
     *
     * @return string
     */
    public function getKeyword(): string
    {
        return trim($this->input('keyword', ''));
    }

    /**
     * 获取页码
     *
     * @return int
     */
    public function getPage(): int
    {
        return (int) $this->input('page', 1);
    }

    /**
     * 获取每页数量
     *
     * @return int
     */
    public function getPageSize(): int
    {
        return (int) $this->input('pageSize', 10);
    }
}
