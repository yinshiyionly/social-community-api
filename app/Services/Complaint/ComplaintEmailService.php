<?php

namespace App\Services\Complaint;

/**
 * 举报邮件服务类
 *
 * 负责管理邮箱与模板的映射配置，提供模板查找功能
 * 支持企业类和政治类举报的邮件模板动态选择
 */
class ComplaintEmailService
{
    /**
     * 企业类举报默认邮件模板
     * 当收件人邮箱不在配置列表中时使用此模板
     */
    const DEFAULT_ENTERPRISE_TEMPLATE = 'emails.complaint_enterprise';

    /**
     * 政治类举报默认邮件模板
     * 当收件人邮箱不在配置列表中时使用此模板
     */
    const DEFAULT_POLITICS_TEMPLATE = 'emails.complaint_politics';

    /**
     * 诽谤类举报默认邮件模板
     * 当收件人邮箱不在配置列表中时使用此模板
     */
    const DEFAULT_DEFAMATION_TEMPLATE = 'emails.complaint_defamation';

    /**
     * 保持向后兼容的默认模板常量
     * @deprecated 请使用 DEFAULT_ENTERPRISE_TEMPLATE
     */
    const DEFAULT_TEMPLATE = 'emails.complaint_enterprise';

    /**
     * 企业类举报邮箱与模板映射配置数组
     *
     * 配置格式：
     * - email: 收件人邮箱地址（精确匹配）
     * - template: 对应的邮件模板视图名称
     */
    const ENTERPRISE_EMAIL_LIST = [
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
     * 政治类举报邮箱与模板映射配置数组
     *
     * 配置格式：
     * - email: 收件人邮箱地址（精确匹配）
     * - template: 对应的邮件模板视图名称
     */
    const POLITICS_EMAIL_LIST = [
        [
            'email' => 'jubao@12377.cn',
            'template' => 'emails.complaint_politics'
        ],
        [
            'email' => 'qinqurn@bytedance.com',
            'template' => 'emails.complaint_politics_douyin'
        ],
        [
            'email' => 'lcz7610@126.com',
            'template' => 'emails.complaint_politics_douyin'
        ],
        [
            'email' => 'yinzhengfan@163.com',
            'template' => 'emails.complaint_politics_douyin'
        ]
    ];

    /**
     * 诽谤类举报邮箱与模板映射配置数组
     *
     * 配置格式：
     * - email: 收件人邮箱地址（精确匹配）
     * - template: 对应的邮件模板视图名称
     */
    const DEFAMATION_EMAIL_LIST = [
        [
            'email' => 'jubao@12377.cn',
            'template' => 'emails.complaint_defamation'
        ],
        [
            'email' => 'qinqurn@bytedance.com',
            'template' => 'emails.complaint_defamation_douyin'
        ],
        [
            'email' => 'lcz7610@126.com',
            'template' => 'emails.complaint_defamation_douyin'
        ],
        [
            'email' => 'yinzhengfan@163.com',
            'template' => 'emails.complaint_defamation_douyin'
        ]
    ];

    /**
     * 保持向后兼容的配置数组
     * @deprecated 请使用 ENTERPRISE_EMAIL_LIST
     */
    const COMPLAINT_EMAIL_LIST = self::ENTERPRISE_EMAIL_LIST;

    /**
     * 根据收件人邮箱地址获取企业类举报对应的邮件模板名称
     *
     * 遍历 ENTERPRISE_EMAIL_LIST 配置数组，查找与传入邮箱地址匹配的配置项。
     * 如果找到匹配项，返回对应的模板名称；否则返回默认模板。
     *
     * @param string $email 收件人邮箱地址
     * @return string 邮件模板视图名称
     */
    public static function getTemplateByEmail(string $email): string
    {
        return self::getEnterpriseTemplateByEmail($email);
    }

    /**
     * 根据收件人邮箱地址获取企业类举报对应的邮件模板名称
     *
     * 遍历 ENTERPRISE_EMAIL_LIST 配置数组，查找与传入邮箱地址匹配的配置项。
     * 如果找到匹配项，返回对应的模板名称；否则返回默认模板。
     *
     * @param string $email 收件人邮箱地址
     * @return string 邮件模板视图名称
     */
    public static function getEnterpriseTemplateByEmail(string $email): string
    {
        // 遍历企业类邮箱模板配置数组
        foreach (self::ENTERPRISE_EMAIL_LIST as $config) {
            // 精确匹配邮箱地址
            if ($config['email'] === $email) {
                return $config['template'];
            }
        }

        // 未找到匹配项，返回企业类默认模板
        return self::DEFAULT_ENTERPRISE_TEMPLATE;
    }

    /**
     * 根据收件人邮箱地址获取政治类举报对应的邮件模板名称
     *
     * 遍历 POLITICS_EMAIL_LIST 配置数组，查找与传入邮箱地址匹配的配置项。
     * 如果找到匹配项，返回对应的模板名称；否则返回默认模板。
     *
     * @param string $email 收件人邮箱地址
     * @return string 邮件模板视图名称
     */
    public static function getPoliticsTemplateByEmail(string $email): string
    {
        // 遍历政治类邮箱模板配置数组
        foreach (self::POLITICS_EMAIL_LIST as $config) {
            // 精确匹配邮箱地址
            if ($config['email'] === $email) {
                return $config['template'];
            }
        }

        // 未找到匹配项，返回政治类默认模板
        return self::DEFAULT_POLITICS_TEMPLATE;
    }

    /**
     * 根据收件人邮箱地址获取诽谤类举报对应的邮件模板名称
     *
     * 遍历 DEFAMATION_EMAIL_LIST 配置数组，查找与传入邮箱地址匹配的配置项。
     * 如果找到匹配项，返回对应的模板名称；否则返回默认模板。
     *
     * @param string $email 收件人邮箱地址
     * @return string 邮件模板视图名称
     */
    public static function getDefamationTemplateByEmail(string $email): string
    {
        // 遍历诽谤类邮箱模板配置数组
        foreach (self::DEFAMATION_EMAIL_LIST as $config) {
            // 精确匹配邮箱地址
            if ($config['email'] === $email) {
                return $config['template'];
            }
        }

        // 未找到匹配项，返回诽谤类默认模板
        return self::DEFAULT_DEFAMATION_TEMPLATE;
    }
}
