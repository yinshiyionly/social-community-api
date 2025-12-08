<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FileUploadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $maxSize = config('filesystems.max_upload_size', 10240); // KB
        $allowedMimes = implode(',', config('filesystems.allowed_mimes', []));

        return [
            // Single file upload
            'file' => [
                'required',
                'file',
                'max:' . $maxSize,
                'mimes:' . $allowedMimes,
            ],
            
            // Multiple file upload
            'files' => 'sometimes|array',
            'files.*' => [
                'file',
                'max:' . $maxSize,
                'mimes:' . $allowedMimes,
            ],
            
            // Storage disk selection (optional)
            'disk' => 'sometimes|string|in:volcengine,local,s3',
            
            // Custom storage path (optional)
            'path' => 'sometimes|string|max:255',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        $maxSizeMB = round(config('filesystems.max_upload_size', 10240) / 1024, 1);
        $allowedTypes = implode('、', config('filesystems.allowed_mimes', []));

        return [
            // Single file validation messages
            'file.required' => '请选择要上传的文件',
            'file.file' => '上传的必须是有效文件',
            'file.max' => '文件大小不能超过 ' . $maxSizeMB . 'MB',
            'file.mimes' => '不支持的文件类型，支持的类型：' . $allowedTypes,
            
            // Multiple files validation messages
            'files.array' => '批量上传文件必须是数组格式',
            'files.*.file' => '批量上传中包含无效文件',
            'files.*.max' => '批量上传中有文件大小超过 ' . $maxSizeMB . 'MB',
            'files.*.mimes' => '批量上传中有不支持的文件类型，支持的类型：' . $allowedTypes,
            
            // Storage disk validation messages
            'disk.string' => '存储驱动必须是字符串',
            'disk.in' => '不支持的存储驱动，支持的驱动：volcengine、local、s3',
            
            // Path validation messages
            'path.string' => '存储路径必须是字符串',
            'path.max' => '存储路径不能超过255个字符',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'file' => '文件',
            'files' => '文件列表',
            'files.*' => '文件',
            'disk' => '存储驱动',
            'path' => '存储路径',
        ];
    }
}