<?php

namespace App\Http\Requests\App;

use App\Http\Requests\App\Traits\PostStoreRequestTrait;
use App\Models\App\AppPostBase;
use App\Models\App\AppTopicBase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * 图文动态发表请求验证
 *
 * 验证规则：
 * - content 和 images 至少有一个非空
 * - content 最大 500 字符
 * - images 最多 9 张图片
 * - images 只能包含图片格式
 * - topics 最多 3 个话题
 */
class ImageTextPostStoreRequest extends FormRequest
{
    use PostStoreRequestTrait;

    /**
     * 最大话题数量
     */
    const MAX_TOPICS = 3;

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
            'images' => 'sometimes|nullable|array|max:9',
            'images.*' => 'required|string|max:500',
            'topics' => 'sometimes|nullable|array|max:' . self::MAX_TOPICS,
            'topics.*.id' => 'required|integer|min:1',
            'topics.*.name' => 'required|string|max:100',
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
            $this->validateTopicsExist($validator);
        });
    }

    /**
     * 验证 content 和 images 至少有一个非空
     *
     * @param Validator $validator
     * @return void
     */
    protected function validateContentOrMedia(Validator $validator): void
    {
        $content = trim($this->input('content', ''));
        $images = $this->input('images', []);

        if (empty($content) && empty($images)) {
            $validator->errors()->add('content', '请输入文字或者上传图片，才可以发布哦');
        }
    }

    /**
     * 验证 images 只包含图片格式
     *
     * @param Validator $validator
     * @return void
     */
    protected function validateImageOnly(Validator $validator): void
    {
        $images = $this->input('images', []);

        if (empty($images) || !is_array($images)) {
            return;
        }

        foreach ($images as $url) {
            if (!$this->isImageUrl($url)) {
                $validator->errors()->add('images', '图文动态只能上传图片');
                return;
            }
        }
    }

    /**
     * 验证话题是否存在且有效
     *
     * @param Validator $validator
     * @return void
     */
    protected function validateTopicsExist(Validator $validator): void
    {
        $topics = $this->input('topics', []);

        if (empty($topics) || !is_array($topics)) {
            return;
        }

        $topicIds = array_column($topics, 'id');
        $topicIds = array_filter($topicIds);

        if (empty($topicIds)) {
            return;
        }

        // 检查话题是否存在且正常状态
        $existCount = AppTopicBase::query()
            ->whereIn('topic_id', $topicIds)
            ->normal()
            ->count();

        if ($existCount !== count($topicIds)) {
            $validator->errors()->add('topics', '选择的话题不存在或已下线');
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
            'images.max' => '最多上传9张图片',
            'images.*.max' => '图片URL过长',
            'topics.max' => '最多选择' . self::MAX_TOPICS . '个话题',
            'topics.*.id.required' => '话题ID不能为空',
            'topics.*.id.integer' => '话题ID格式错误',
            'topics.*.name.required' => '话题名称不能为空',
        ]);
    }

    /**
     * 获取验证后的数据并设置默认值
     *
     * @return array
     */
    public function validatedWithDefaults(): array
    {
        $validated = $this->validated();

        // 将 images 转换为 media_data 以兼容后续处理
        if (isset($validated['images'])) {
            $validated['media_data'] = $validated['images'];
            unset($validated['images']);
        }

        $data = $this->applyDefaults($validated, AppPostBase::POST_TYPE_IMAGE_TEXT);

        // 图文动态：如果未指定封面，使用第一张图片作为封面
        /*if (!empty($data['media_data']) && empty($data['cover']['url'])) {
            $data['cover'] = $data['media_data'][0];
        }*/

        // 处理话题数据
        $data['topics'] = $this->input('topics', []);

        return $data;
    }
}
