<?php

namespace App\Http\Requests\App;

use App\Http\Requests\App\Traits\PostStoreRequestTrait;
use App\Models\App\AppPostBase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * 视频动态发表请求验证
 *
 * 请求参数：
 * - videoUrl: 必填，视频URL字符串
 * - coverUrl: 可选，自定义封面URL
 * - content: 可选，最大500字符
 * - topics: 可选，话题数组
 *
 * 验证规则：
 * - videoUrl 必填，必须是视频格式
 * - content 最大 500 字符
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
            'videoUrl' => 'required|string|max:500',
            'coverUrl' => 'sometimes|nullable|string|max:500',
            'topics' => 'sometimes|nullable|array',
            'topics.*.id' => 'required_with:topics|integer',
            'topics.*.name' => 'required_with:topics|string|max:50',
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
            $this->validateVideoUrl($validator);
        });
    }

    /**
     * 验证 videoUrl 是视频格式
     *
     * @param Validator $validator
     * @return void
     */
    protected function validateVideoUrl(Validator $validator): void
    {
        $videoUrl = $this->input('videoUrl', '');

        if (empty($videoUrl)) {
            return;
        }

        if (!$this->isVideoUrl($videoUrl)) {
            $validator->errors()->add('videoUrl', '请上传正确的视频格式');
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
            'videoUrl.required' => '请上传视频',
            'videoUrl.string' => '视频URL格式不正确',
            'videoUrl.max' => '视频URL过长',
            'coverUrl.string' => '封面URL格式不正确',
            'coverUrl.max' => '封面URL过长',
            'topics.array' => '话题格式不正确',
            'topics.*.id.required_with' => '话题ID不能为空',
            'topics.*.id.integer' => '话题ID必须是整数',
            'topics.*.name.required_with' => '话题名称不能为空',
        ]);
    }

    /**
     * 获取验证后的数据并设置默认值
     *
     * @return array
     */
    public function validatedWithDefaults(): array
    {
        $data = $this->validated();

        // 将 videoUrl 转换为 media_data 格式
        $data['media_data'] = [$data['videoUrl']];
        unset($data['videoUrl']);

        // 将 coverUrl 转换为 cover 格式
        if (!empty($data['coverUrl'])) {
            $data['cover'] = $data['coverUrl'];
        }
        unset($data['coverUrl']);

        return $this->applyDefaults($data, AppPostBase::POST_TYPE_VIDEO);
    }
}
