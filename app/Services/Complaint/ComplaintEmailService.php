<?php

namespace App\Services\Complaint;

/**
 * 举报邮件服务类
 *
 * 负责管理邮箱与模板的映射配置，提供模板查找功能
 */
class ComplaintEmailService
{
    /**
     * 默认邮件模板
     * 当收件人邮箱不在配置列表中时使用此模板
     */
    const DEFAULT_TEMPLATE = 'emails.complaint_enterprise';

    /**
     * 邮箱与模板映射配置数组
     *
     * 配置格式：
     * - email: 收件人邮箱地址（精确匹配）
     * - template: 对应的邮件模板视图名称
     */
    const COMPLAINT_EMAIL_LIST = [
        [
            'email' => 'jubao@12377.cn',
            'template' => 'emails.complaint_enterprise'
        ],
        [
            'email' => 'qinqurn@bytedance.com',
            'template' => 'emails.complaint_enterprise_douyin'
        ],
        [
            'email' => 'lcz7610@126.com',
            'template' => 'emails.complaint_enterprise_douyin'
        ],
        [
            'email' => 'yinzhengfan@163.com',
            'template' => 'emails.complaint_enterprise_douyin'
        ]
    ];

    /**
     * 根据收件人邮箱地址获取对应的邮件模板名称
     *
     * 遍历 COMPLAINT_EMAIL_LIST 配置数组，查找与传入邮箱地址匹配的配置项。
     * 如果找到匹配项，返回对应的模板名称；否则返回默认模板。
     *
     * @param string $email 收件人邮箱地址
     * @return string 邮件模板视图名称
     */
    public static function getTemplateByEmail(string $email): string
    {
        // 遍历邮箱模板配置数组
        foreach (self::COMPLAINT_EMAIL_LIST as $config) {
            // 精确匹配邮箱地址
            if ($config['email'] === $email) {
                return $config['template'];
            }
        }

        // 未找到匹配项，返回默认模板
        return self::DEFAULT_TEMPLATE;
    }
}
