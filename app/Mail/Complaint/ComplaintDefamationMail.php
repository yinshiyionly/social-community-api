<?php

namespace App\Mail\Complaint;

use App\Services\Complaint\ComplaintEmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * 诽谤类举报邮件
 *
 * 用于发送诽谤类举报信息的邮件，包含举报详情和相关附件材料
 * 支持动态模板选择，通过 ComplaintEmailService::getDefamationTemplateByEmail 根据收件人邮箱获取对应模板
 * 使用独立的Blade模版文件，便于后续维护和扩展
 */
class ComplaintDefamationMail extends Mailable
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
     * @param string|null $templateName 邮件模板名称，为空时使用默认模板
     */
    public function __construct(array $mailData, array $attachmentPaths = [], ?string $templateName = null)
    {
        $this->mailData = $mailData;
        $this->attachmentPaths = $attachmentPaths;
        // 如果未传入模板名称，使用默认诽谤类模板
        $this->templateName = $templateName ?? ComplaintEmailService::DEFAULT_DEFAMATION_TEMPLATE;
    }

    /**
     * 构建邮件
     *
     * 设置邮件主题、视图模版和附件
     * 使用动态模板名称，支持根据收件人邮箱选择不同的邮件模板
     *
     * @return $this
     */
    public function build()
    {
        // 设置邮件主题和视图（使用动态模板名称）
        $mail = $this->subject($this->mailData['subject'] ?? '诽谤类举报信息')
            ->view($this->templateName)
            ->with('data', $this->mailData);

        // 添加附件
        foreach ($this->attachmentPaths as $attachment) {
            // 验证附件路径是否存在
            if (!isset($attachment['path']) || !file_exists($attachment['path'])) {
                Log::channel('daily')->warning('诽谤类举报邮件附件文件不存在', [
                    'file_path' => $attachment['path'] ?? 'unknown',
                    'file_name' => $attachment['name'] ?? 'unknown',
                    'template_name' => $this->templateName,
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
