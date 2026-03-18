<?php

namespace App\Http\Requests\Admin\Traits;

/**
 * 课程创建/更新请求的公共校验规则。
 *
 * 职责：
 * 1. 统一课程 upsert 的字段规则，避免 store/update 规则漂移；
 * 2. 统一错误文案，确保管理端在创建与更新场景提示一致；
 * 3. 明确必填字段边界，降低前后端联调歧义。
 */
trait CourseUpsertRequestTrait
{
    /**
     * 课程 upsert 公共规则。
     *
     * 约束：
     * 1. 必填字段在创建与更新保持一致；
     * 2. 可选字段仅在显式传入时参与写入；
     * 3. 枚举字段统一按课程模型约定校验。
     *
     * @return array<string, string>
     */
    protected function courseUpsertRules(): array
    {
        return [
            'categoryId' => 'required|integer|min:1',
            'courseTitle' => 'required|string|max:200',
            'courseSubtitle' => 'nullable|string|max:300',
            'payType' => 'required|integer|in:1,2,3',
            'playType' => 'required|integer|in:1,2,3,4',
            'scheduleType' => 'required|integer|in:1,2',
            'teacherName' => 'required|string|max:100',
            'classTeacherName' => 'required|string|max:100',
            'classTeacherQr' => 'required|string|max:500',
            'coverImage' => 'required|string|max:500',
            'itemImage' => 'required|string|max:500',
            'description' => 'required|string',
            'remark' => 'nullable|string',
            'originalPrice' => 'required|numeric|min:0|max:9999999.99',
            'currentPrice' => 'required|numeric|min:0|max:9999999.99',
            'isFree' => 'required|integer|in:0,1',
            'status' => 'required|integer|in:0,1,2',
            'publishTime' => 'nullable|date',
        ];
    }

    /**
     * 课程 upsert 公共错误文案。
     *
     * @return array<string, string>
     */
    protected function courseUpsertMessages(): array
    {
        return [
            'categoryId.required' => '请选择课程分类',
            'categoryId.integer' => '分类ID必须是整数',
            'categoryId.min' => '分类ID必须大于0',
            'courseTitle.required' => '课程标题不能为空',
            'courseTitle.max' => '课程标题不能超过200个字符',
            'courseSubtitle.max' => '课程副标题不能超过300个字符',
            'payType.required' => '请选择付费类型',
            'payType.in' => '付费类型无效',
            'playType.required' => '请选择播放类型',
            'playType.in' => '播放类型无效',
            'scheduleType.required' => '请选择排课类型',
            'scheduleType.in' => '排课类型无效',
            'teacherName.required' => '主讲老师名称不能为空',
            'teacherName.max' => '主讲老师名称不能超过100个字符',
            'classTeacherName.required' => '班主任名称不能为空',
            'classTeacherName.max' => '班主任名称不能超过100个字符',
            'classTeacherQr.required' => '班主任二维码地址不能为空',
            'classTeacherQr.max' => '班主任二维码地址不能超过500个字符',
            'coverImage.required' => '封面图地址不能为空',
            'coverImage.max' => '封面图地址不能超过500个字符',
            'itemImage.required' => '详情图地址不能为空',
            'itemImage.max' => '详情图地址不能超过500个字符',
            'description.required' => '课程描述不能为空',
            'originalPrice.required' => '原价不能为空',
            'originalPrice.numeric' => '原价必须是数字',
            'originalPrice.min' => '原价不能小于0',
            'currentPrice.required' => '现价不能为空',
            'currentPrice.numeric' => '现价必须是数字',
            'currentPrice.min' => '现价不能小于0',
            'isFree.required' => '请选择是否免费',
            'isFree.in' => '是否免费值无效',
            'status.required' => '请选择课程状态',
            'status.in' => '状态值无效',
            'publishTime.date' => '发布时间格式无效',
        ];
    }
}
