<?php

declare(strict_types=1);

namespace App\Http\Requests\Complaint\ComplaintPolitics;

use App\Models\PublicRelation\ComplaintPolitics;

/**
 * 更新政治类投诉请求验证类
 *
 * 继承 CreateComplaintPoliticsRequest 的所有验证规则
 * 添加 id 字段验证（required、exists）
 */
class UpdateComplaintPoliticsRequest extends CreateComplaintPoliticsRequest
{
    /**
     * Get the validation rules that apply to the request.
     * 继承创建请求的验证规则，并添加 id 字段验证
     *
     * @return array
     */
    public function rules(): array
    {
        // 获取父类的验证规则
        $rules = parent::rules();

        // 添加 id 字段验证：必填、整数、存在于 complaint_politics 表
        $rules['id'] = ['required', 'integer', function ($attr, $value, $fail) {
            $exists = ComplaintPolitics::query()
                ->where('id', $value)
                ->exists();
            if (!$exists) {
                $fail('记录不存在');
            }
        }];

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     * 继承创建请求的错误消息，并添加 id 字段的错误消息
     *
     * @return array
     */
    public function messages(): array
    {
        // 获取父类的错误消息
        $messages = parent::messages();

        // 添加 id 字段的中文错误提示信息
        $messages['id.required'] = 'ID不能为空';
        $messages['id.integer'] = 'ID必须是整数';
        $messages['id.exists'] = '记录不存在';

        return $messages;
    }
}
