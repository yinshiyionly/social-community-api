<?php

namespace App\Mail\Detection\Task;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DetectionTaskWarnMail extends Mailable
{
    use Queueable, SerializesModels;

    protected string $template;
    protected array $mailData;

    const TEMPLATES = [
        'template1' => 'emails.detection_task_warn_1', // 预警通知模版1
    ];

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $template, array $mailData)
    {
        $this->template = $template;
        $this->mailData = $mailData;

        // 设置队列名称，便于 Horizon 监控和管理
        $this->onQueue('detection-email');
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // 获取邮件模版配置
        $view = self::TEMPLATES[$this->template] ?? self::TEMPLATES['template1'];
        $mail = $this->subject($this->mailData['subject'] ?? '预警通知')
            ->view($view)
            ->with('data', $this->mailData);
        return $mail;
    }
}
