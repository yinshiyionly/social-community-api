<?php

namespace App\Mail\Complaint;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * 企业类举报邮件
 *
 * 用于发送企业类举报信息的邮件，包含举报详情和相关附件材料
 * 使用独立的Blade模版文件，便于后续维护和扩展
 * 支持根据收件人邮箱域名选择不同的邮件模板：
 * - @bytedance.com 域名使用抖音专用模板
 * - 其他域名使用默认模板
 */
class ComplaintEnterpriseMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * 邮件数据
     * @var array
     */
    protected array $mailData;

    /**
     * 附件文件路径列表
     * @var array
     */
    protected array $attachmentPaths;

    /**
     * 是否使用抖音专用模板
     * @var bool
     */
    protected bool $useDouyinTemplate;

    /**
     * 构造函数
     *
     * @param array $mailData 邮件数据，包含举报信息和举报人信息
     * @param array $attachmentPaths 附件文件路径列表，每个元素包含 path, name, mime
     * @param bool $useDouyinTemplate 是否使用抖音专用模板，默认 false
     */
    public function __construct(array $mailData, array $attachmentPaths = [], bool $useDouyinTemplate = false)
    {
        $this->mailData = $mailData;
        $this->attachmentPaths = $attachmentPaths;
        $this->useDouyinTemplate = $useDouyinTemplate;
    }

    /**
     * 构建邮件
     *
     * 设置邮件主题、视图模版和附件
     * 根据 useDouyinTemplate 参数选择不同的邮件模板：
     * - true: 使用抖音专用模板 emails.complaint_enterprise_douyin
     * - false: 使用默认模板 emails.complaint_enterprise
     *
     * @return $this
     */
    public function build()
    {
        // 根据参数选择邮件模板视图
        // 抖音模板: resources/views/emails/complaint_enterprise_douyin.blade.php
        // 默认模板: resources/views/emails/complaint_enterprise.blade.php
        $viewName = $this->useDouyinTemplate
            ? 'emails.complaint_enterprise_douyin'
            : 'emails.complaint_enterprise';

        // 设置邮件主题和视图
        $mail = $this->subject($this->mailData['subject'] ?? '企业类举报信息')
            ->view($viewName)
            ->with('data', $this->mailData);

        // 添加附件
        foreach ($this->attachmentPaths as $attachment) {
            // 验证附件路径是否存在
            if (!isset($attachment['path']) || !file_exists($attachment['path'])) {
                Log::channel('daily')->warning('企业类举报邮件附件文件不存在', [
                    'file_path' => $attachment['path'] ?? 'unknown',
                    'file_name' => $attachment['name'] ?? 'unknown',
                ]);
                continue;
            }

            // 添加附件到邮件
            $mail->attach($attachment['path'], [
                'as' => $attachment['name'] ?? basename($attachment['path']),
                'mime' => $attachment['mime'] ?? null,
            ]);
        }

        return $mail;
    }
}
