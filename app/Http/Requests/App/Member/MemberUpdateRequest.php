<?php

namespace App\Http\Requests\App\Member;

use App\Http\Resources\App\AppApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * 更新个人信息请求验证
 */
class MemberUpdateRequest extends FormRequest
{
    /**
     * 确定用户是否有权限发出此请求
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 获取应用于请求的验证规则
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'nickname' => 'sometimes|string|max:20',
            'avatar' => 'sometimes|string|max:500',
            'gender' => 'sometimes|integer|in:0,1,2',
            'birthday' => 'sometimes|nullable|date|before:today',
            'bio' => 'sometimes|nullable|string|max:200',
        ];
    }

    /**
     * 获取验证错误的自定义消息
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'nickname.max' => '昵称不能超过20个字符',
            'avatar.max' => '头像地址过长',
            'gender.in' => '性别参数无效',
            'birthday.date' => '生日格式不正确',
            'birthday.before' => '生日不能是未来日期',
            'bio.max' => '个人简介不能超过200个字符',
        ];
    }

    /**
     * 处理验证失败
     *
     * @param Validator $validator
     * @throws HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors()->first();
        throw new HttpResponseException(AppApiResponse::error($errors));
    }
}
