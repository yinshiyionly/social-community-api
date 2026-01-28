<?php

namespace App\Http\Requests\App;

use App\Http\Requests\App\Traits\PostStoreRequestTrait;
use App\Models\App\AppPostBase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * 图文动态发表请求验证
 *
 * 验证规则：
 * - content 和 media_data 至少有一个非空
 * - content 最大 500 字符
 * - media_data 最多 9 张图片
 * - media_data 只能包含图片格式
 */
class ImageTextPostStoreRequest extends FormRequest
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
            'content' => 'sometimes|nullable|string|max:500',
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
            $this->validateContentOrMedia($validator);
            $this->validateImageOnly($validator);
        });
    }

    /**
     * 验证 content 和 media_data 至少有一个非空
     *
     * @param Validator $validator
     * @return void
     */
    protected function validateContentOrMedia(Validator $validator): void
    {
        $content = trim($this->input('content', ''));
        $mediaData = $this->input('media_data', []);

        if (empty($content) && empty($mediaData)) {
            $validator->errors()->add('content', '请输入文字或者上传图片，才可以发布哦');
        }
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
                $validator->errors()->add('media_data', '图文动态只能上传图片');
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
            'content.max' => '内容最多500字',
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
        $data = $this->applyDefaults(
            $this->validated(),
            AppPostBase::POST_TYPE_IMAGE_TEXT
        );

        // 图文动态：如果未指定封面，使用第一张图片作为封面
        if (!empty($data['media_data']) && empty($data['cover'])) {
            $data['cover'] = $data['media_data'][0];
        }

        return $data;
    }
}
