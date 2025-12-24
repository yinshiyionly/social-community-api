<?php

namespace App\Mail\ReportMailTest;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * 邮箱配置测试邮件
 *
 * 用于测试邮箱配置是否正确，验证SMTP连接和发送功能
 */
class ReportEmailTestMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * 邮箱配置信息
     * @var array
     */
    protected array $configData;

    /**
     * 构造函数
     *
     * @param array $configData 邮箱配置信息
     */
    public function __construct(array $configData)
    {
        $this->configData = $configData;
    }

    /**
     * 构建邮件
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('邮箱配置测试 - 测试邮件')
            ->view('emails.report_email_test')
            ->with('data', $this->configData);
    }
}
