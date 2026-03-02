<?php

namespace App\Http\Requests\App;

use App\Http\Requests\App\Traits\PostStoreRequestTrait;
use App\Models\App\AppPostBase;
use App\Services\App\ArticleContentMediaParser;
use Illuminate\Foundation\Http\FormRequest;

/**
 * 文章动态发表请求验证
 *
 * 验证规则：
 * - content 必填，最大 10000 字符
 */
class ArticlePostStoreRequest extends FormRequest
{
    use PostStoreRequestTrait;

    /**
     * 确定用户是否有权限发起此请求
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 获取适用于请求的验证规则
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:50',
            'content' => 'required|string|max:10000',
            'cover' => 'sometimes|nullable|string|max:500',
            'image_show_style' => 'sometimes|integer|in:1,2',
            'article_cover_style' => 'sometimes|integer|in:1,2,3',
            'visible' => 'sometimes|integer|in:0,1',
        ];
    }

    /**
     * 获取验证错误的自定义消息
     *
     * @return array
     */
    public function messages(): array
    {
        return array_merge($this->commonMessages(), [
            'content.required' => '请输入文章内容',
            'content.max' => '文章内容最多10000字',
        ]);
    }

    /**
     * 获取验证后的数据并设置默认值
     *
     * @return array
     */
    public function validatedWithDefaults(): array
    {
        $data = $this->applyDefaults(
            $this->validated(),
            AppPostBase::POST_TYPE_ARTICLE
        );

        $parsed = app(ArticleContentMediaParser::class)->parse((string)($data['content'] ?? ''));
        $data['content'] = $parsed['content'];
        $data['media_data'] = $parsed['media_data'];

        if (!empty($parsed['cover'])) {
            $data['cover'] = $parsed['cover'];
        }

        return $data;
    }
}
