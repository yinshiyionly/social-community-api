<?php

namespace App\Mail\Complaint;

use App\Services\Complaint\ComplaintEmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * 企业类举报邮件
 *
 * 用于发送企业类举报信息的邮件，包含举报详情和相关附件材料
 * 使用独立的Blade模版文件，便于后续维护和扩展
 * 支持通过模板名称参数指定使用的邮件模板
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
     * 邮件模板名称
     * @var string
     */
    protected string $templateName;

    /**
     * 构造函数
     *
     * @param array $mailData 邮件数据，包含举报信息和举报人信息
     * @param array $attachmentPaths 附件文件路径列表，每个元素包含 path, name, mime
     * @param string $templateName 邮件模板视图名称，默认使用 ComplaintEmailService::DEFAULT_TEMPLATE
     */
    public function __construct(array $mailData, array $attachmentPaths = [], string $templateName = ComplaintEmailService::DEFAULT_ENTERPRISE_TEMPLATE)
    {
        $this->mailData = $mailData;
        $this->attachmentPaths = $attachmentPaths;
        $this->templateName = $templateName;
    }

    /**
     * 构建邮件
     *
     * 设置邮件主题、视图模版和附件
     * 使用构造函数传入的模板名称渲染邮件内容
     *
     * @return $this
     */
    public function build()
    {
        // 使用传入的模板名称渲染邮件
        // 模板文件位于 resources/views/ 目录下
        $mail = $this->subject($this->mailData['subject'] ?? '企业类举报信息')
            ->view($this->templateName)
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
