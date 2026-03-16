<?php

namespace App\Http\Requests\App;

/**
 * 图文动态更新请求验证。
 *
 * 设计约束：
 * 1. 复用图文发帖字段规则，确保“发布/更新”参数语义一致；
 * 2. 额外要求 postId 指定被更新帖子；
 * 3. 更新采用全量覆盖提交，不提供 patch 语义。
 */
class ImageTextPostUpdateRequest extends ImageTextPostStoreRequest
{
    /**
     * 获取更新接口校验规则。
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'postId' => 'required|numeric|min:1',
        ]);
    }

    /**
     * 获取更新接口校验错误文案。
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'postId.required' => 'postId不能为空',
            'postId.numeric' => 'postId格式错误',
            'postId.min' => 'postId必须大于0',
        ]);
    }

    /**
     * 获取标准化后的更新数据。
     *
     * @return array<string, mixed>
     */
    public function validatedWithDefaults(): array
    {
        $data = parent::validatedWithDefaults();
        $data['postId'] = (int)$this->input('postId');

        return $data;
    }
}
