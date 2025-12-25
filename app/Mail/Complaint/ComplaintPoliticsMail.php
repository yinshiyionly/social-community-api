<?php

namespace App\Mail\Complaint;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * 政治类举报邮件
 *
 * 用于发送政治类举报信息的邮件，包含举报详情和相关附件材料
 * 使用独立的Blade模版文件，便于后续维护和扩展
 */
class ComplaintPoliticsMail extends Mailable
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
     * 构造函数
     *
     * @param array $mailData 邮件数据，包含举报信息和举报人信息
     * @param array $attachmentPaths 附件文件路径列表，每个元素包含 path, name, mime
     */
    public function __construct(array $mailData, array $attachmentPaths = [])
    {
        $this->mailData = $mailData;
        $this->attachmentPaths = $attachmentPaths;
    }

    /**
     * 构建邮件
     *
     * 设置邮件主题、视图模版和附件
     *
     * @return $this
     */
    public function build()
    {
        // 设置邮件主题和视图
        $mail = $this->subject($this->mailData['subject'] ?? '政治类举报信息')
            ->view('emails.complaint_politics')
            ->with('data', $this->mailData);

        // 添加附件
        foreach ($this->attachmentPaths as $attachment) {
            // 验证附件路径是否存在
            if (!isset($attachment['path']) || !file_exists($attachment['path'])) {
                Log::channel('daily')->warning('政治类举报邮件附件文件不存在', [
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
