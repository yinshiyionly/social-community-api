<?php

namespace App\Http\Requests\App;

use App\Http\Requests\App\Traits\PostStoreRequestTrait;
use App\Models\App\AppPostBase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * 视频动态发表请求验证
 *
 * 验证规则：
 * - media_data 必填，且只能包含 1 个视频
 * - content 最大 500 字符
 * - media_data 只能包含视频格式
 */
class VideoPostStoreRequest extends FormRequest
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
            'media_data' => 'required|array|size:1',
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
            $this->validateVideoOnly($validator);
        });
    }

    /**
     * 验证 media_data 只包含视频格式
     *
     * @param Validator $validator
     * @return void
     */
    protected function validateVideoOnly(Validator $validator): void
    {
        $mediaData = $this->input('media_data', []);

        if (empty($mediaData) || !is_array($mediaData)) {
            return;
        }

        foreach ($mediaData as $url) {
            if (!$this->isVideoUrl($url)) {
                $validator->errors()->add('media_data', '视频动态只能上传视频');
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
            'media_data.required' => '请上传视频',
            'media_data.size' => '视频动态只能上传1个视频',
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
            AppPostBase::POST_TYPE_VIDEO
        );
    }
}
