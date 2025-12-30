<?php

declare(strict_types=1);

namespace App\Http\Requests\PublicRelation\MaterialPolitics;

use App\Models\PublicRelation\MaterialPolitics;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateMaterialPoliticsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            // Optional fields - matching DDL structure
            'name' => ['required', 'string', 'max:50', function ($attr, $value, $fail) {
                $exists = MaterialPolitics::query()
                    ->where('name', $value)
                    ->exists();
                if ($exists) {
                    $fail('姓名已经存在');
                }
            }],
            'gender' => ['required', 'integer', Rule::in([
                MaterialPolitics::GENDER_UNKNOWN,
                MaterialPolitics::GENDER_MALE,
                MaterialPolitics::GENDER_FEMALE,
            ])],
            'contact_phone' => 'required|string|max:20',
            'contact_email' => 'required|email|max:50',
            'province_code' => 'required|integer|min:0',
            'city_code' => 'required|integer|min:0',
            'district_code' => 'required|integer|min:0',
            'contact_address' => 'required|string|max:255',

            // Report material array validation
            // 'report_material' => 'required|array',
            // 'report_material.*.name' => 'required_with:report_material|string',
            // 'report_material.*.url' => 'required_with:report_material|string',

            // Status validation
            'status' => ['nullable', 'integer', Rule::in([
                MaterialPolitics::STATUS_ENABLED,
                MaterialPolitics::STATUS_DISABLED,
            ])],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            // Name validation messages
            'name.string' => '姓名必须是字符串',
            'name.max' => '姓名不能超过50个字符',
            'name.unique' => '姓名已经存在',

            // Gender validation messages
            'gender.required' => '性别不能为空',
            'gender.integer' => '性别必须是整数',
            'gender.in' => '性别值无效',

            // Contact phone validation messages
            'contact_phone.required' => '有效电话不能为空',
            'contact_phone.string' => '有效电话必须是字符串',
            'contact_phone.max' => '有效电话不能超过20个字符',

            // Contact email validation messages
            'contact_email.required' => '电子邮件格式不能为空',
            'contact_email.email' => '电子邮件格式不正确',
            'contact_email.max' => '电子邮件不能超过50个字符',

            // Province code validation messages
            'province_code.required' => '省份代码不能为空',
            'province_code.integer' => '省份代码必须是整数',
            'province_code.min' => '省份代码不能为负数',

            // City code validation messages
            'city_code.required' => '城市代码不能为空',
            'city_code.integer' => '城市代码必须是整数',
            'city_code.min' => '城市代码不能为负数',

            // District code validation messages
            'district_code.required' => '区县代码不能为空',
            'district_code.integer' => '区县代码必须是整数',
            'district_code.min' => '区县代码不能为负数',

            // Contact address validation messages
            'contact_address.required' => '通讯地址不能为空',
            'contact_address.string' => '通讯地址必须是字符串',
            'contact_address.max' => '通讯地址不能超过255个字符',

            // Report material validation messages
            'report_material.required' => '举报材料不能为空',
            'report_material.array' => '举报材料必须是数组',
            'report_material.*.name.required_with' => '举报材料的文件名称不能为空',
            'report_material.*.name.string' => '举报材料的文件名称必须是字符串',
            'report_material.*.url.required_with' => '举报材料的文件地址不能为空',
            'report_material.*.url.string' => '举报材料的文件地址必须是字符串',

            // Status validation messages
            'status.integer' => '状态必须是整数',
            'status.in' => '状态值无效',
        ];
    }
}
