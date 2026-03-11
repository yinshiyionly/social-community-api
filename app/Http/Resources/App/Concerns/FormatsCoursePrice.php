<?php

namespace App\Http\Resources\App\Concerns;

/**
 * 课程金额格式化能力。
 *
 * 设计约束：
 * 1. 统一处理 current_price / original_price 的去尾零展示；
 * 2. 非数值输入兜底为 0，避免前端渲染异常；
 * 3. 仅用于响应展示，不参与金额计算与入库。
 */
trait FormatsCoursePrice
{
    /**
     * 格式化金额为字符串。
     *
     * 规则：
     * - 0.00 => "0"
     * - 0.10 => "0.1"
     * - 99.00 => "99"
     * - 99.10 => "99.1"
     *
     * @param mixed $value
     * @return string
     */
    protected function formatPriceString($value): string
    {
        return $this->normalizePriceValue($value);
    }

    /**
     * 格式化金额为数值类型。
     *
     * 规则：
     * - 无小数部分返回 int；
     * - 有小数部分返回 float；
     * - 非法值兜底 0。
     *
     * @param mixed $value
     * @return int|float
     */
    protected function formatPriceNumber($value)
    {
        $normalized = $this->normalizePriceValue($value);

        if (strpos($normalized, '.') === false) {
            return (int)$normalized;
        }

        return (float)$normalized;
    }

    /**
     * 将金额规整为去尾零字符串。
     *
     * 说明：
     * - 统一按两位小数进行规范化，兼容数据库 decimal(10,2) 字段；
     * - null、空串、非数值统一返回 "0"。
     *
     * @param mixed $value
     * @return string
     */
    private function normalizePriceValue($value): string
    {
        if (is_string($value)) {
            $value = trim($value);
        }

        if (!is_numeric($value)) {
            return '0';
        }

        $formatted = number_format((float)$value, 2, '.', '');
        $formatted = rtrim(rtrim($formatted, '0'), '.');

        if ($formatted === '' || $formatted === '-0') {
            return '0';
        }

        return $formatted;
    }
}
