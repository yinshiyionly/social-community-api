<?php

namespace App\Http\Requests\App;

use App\Http\Requests\App\Traits\PostStoreRequestTrait;
use App\Models\App\AppPostBase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * 文章动态发表请求验证
 *
 * 验证规则：
 * - content 必填，最大 10000 字符
 * - media_data 最多 9 张图片
 * - media_data 只能包含图片格式
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
        return array_merge($this->commonRules(), [
            'content' => 'required|string|max:10000',
            'media_data' => 'sometimes|nullable|array|max:9',
            'media_data.*' => 'required|string|max:500',
        ]);
    }

    /**
     * 配置验证器实例
     *
     * @param Validator $validator
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $this->validateImageOnly($validator);
        });
    }

    /**
     * 验证 media_data 只包含图片格式
     *
     * @param Validator $validator
     * @return void
     */
    protected function validateImageOnly(Validator $validator): void
    {
        $mediaData = $this->input('media_data', []);

        if (empty($mediaData) || !is_array($mediaData)) {
            return;
        }

        foreach ($mediaData as $url) {
            if (!$this->isImageUrl($url)) {
                $validator->errors()->add('media_data', '文章动态只能上传图片');
                return;
            }
        }
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
            'media_data.max' => '最多上传9张图片',
            'media_data.*.max' => '媒体文件URL过长',
        ]);
    }

    /**
     * 获取验证后的数据并设置默认值
     *
     * @return array
     */
    public function validatedWithDefaults(): array
    {
        return $this->applyDefaults(
            $this->validated(),
            AppPostBase::POST_TYPE_ARTICLE
        );
    }
}
