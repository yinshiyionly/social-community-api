<?php

namespace App\Http\Requests\App;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 搜索请求验证
 */
class SearchRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'keyword' => 'required|string|max:100',
            'source' => 'sometimes|string|in:all,member,post',
        ];
    }

    public function messages()
    {
        return [
            'keyword.required' => '搜索关键词不能为空',
            'keyword.string' => '搜索关键词格式错误',
            'keyword.max' => '搜索关键词不能超过100个字符',
            'source.string' => '搜索来源格式错误',
            'source.in' => '搜索来源参数无效',
        ];
    }

    /**
     * 获取处理后的关键词（trim）
     *
     * @return string
     */
    public function getKeyword(): string
    {
        return trim($this->input('keyword', ''));
    }

    /**
     * 获取搜索来源，默认 all
     *
     * @return string
     */
    public function getSource(): string
    {
        return $this->input('source', 'all');
    }
}
