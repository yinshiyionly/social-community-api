<?php

namespace App\Jobs\Complaint;

use App\Models\PublicRelation\ComplaintEnterpriseSendHistory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Throwable;

/**
 * 企业类举报邮件发送队列任务
 *
 * 负责异步发送企业类举报邮件，并记录每次发送的历史记录
 * 包括发送成功和失败的情况都会保存到 complaint_enterprise_send_history 表
 */
class ComplaintEnterpriseSendMailJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 任务最大尝试次数
     *
     * @var int
     */
    public $tries = 3;

    /**
     * 任务超时时间（秒）
     *
     * @var int
     */
    public $timeout = 300;

    /**
     * 任务失败前的最大异常次数
     *
     * @var int
     */
    public $maxExceptions = 3;

    /**
     * 重试延迟时间（秒）
     *
     * @var int
     */
    public $backoff = 60;

    /**
     * 任务参数
     *
     * @var array
     */
    protected array $params;

    /**
     * Create a new job instance.
     *
     * @param array $params 任务参数，包含：
     *                      - complaint_id: 举报记录ID
     *                      - recipient_email: 收件人邮箱
     *                      - operator_id: 操作人ID（可选，默认0）
     *                      - operator_name: 操作人姓名（可选，默认'系统'）
     */
    public function __construct(array $params)
    {
        $this->params = $params;
        // 设置队列名称，便于 Horizon 监控和管理
        $this->onQueue('complaint-enterprise');
    }

    /**
     * 唯一锁的过期时间（秒）
     * 防止任务失败后锁永久存在
     *
     * @var int
     */
    public $uniqueFor = 3600;

    /**
     * 获取任务的唯一标识，防止重复任务
     *
     * @return string
     */
    public function uniqueId(): string
    {
        // 使用 complaint_id 和 recipient_email 拼接
        if (!empty($this->params['complaint_id']) && isset($this->params['recipient_email'])) {
            return $this->params['complaint_id'] . '^' . $this->params['recipient_email'];
        }
        return '';
    }

    /**
     * Execute the job.
     *
     * 执行邮件发送任务，并在发送成功后保存历史记录
     * 根据 use_douyin_template 参数决定使用哪个邮件模板：
     * - true: 使用抖音专用模板（针对 @bytedance.com 域名）
     * - false: 使用默认模板
     *
     * @throws \Exception
     */
    public function handle()
    {
        Log::channel('job')->info('[企业类举报邮件发送队列开始]', [
            'params' => $this->params ?? [],
            'attempt' => $this->attempts()
        ]);

        // 初始化变量，用于保存历史记录
        $mailData = [];
        $renderedHtml = '';

        try {
            // 0. 检查参数
            $this->validateParams();

            // 1. 实例化文件上传服务类
            $fileService = new \App\Services\FileUploadService();
            // 2. 实例化企业类举报服务类
            $service = new \App\Services\Complaint\ComplaintEnterpriseService($fileService);

            // 3. 获取举报记录并准备邮件数据（用于保存历史记录）
            $complaint = $service->getById((int)$this->params['complaint_id']);
            $mailData = $service->prepareMailData($complaint);

            // 4. 判断是否使用抖音模板（从控制器传递的参数获取）
            $useDouyinTemplate = $this->params['use_douyin_template'] ?? false;

            // 5. 渲染邮件HTML内容（用于保存历史记录，根据模板类型选择不同视图）
            $renderedHtml = $this->renderMailHtml($mailData, $useDouyinTemplate);

            // 6. 发送邮件（传递模板选择参数）
            $service->sendEmail(
                (int)$this->params['complaint_id'],
                $this->params['recipient_email'],
                $useDouyinTemplate
            );

            // 7. 发送成功，保存历史记录（状态为成功）
            $this->saveSendHistory(
                $mailData,
                $renderedHtml,
                ComplaintEnterpriseSendHistory::SEND_STATUS_SUCCESS
            );

            return true;
        } catch (\Exception $e) {
            $msg = '[企业类举报邮件发送队列失败]: ' . $e->getMessage();
            Log::channel('job')->error($msg, [
                'params' => $this->params ?? [],
                'attempt' => $this->attempts(),
                'msg' => $e->getMessage()
            ]);

            // 发送失败，保存历史记录（状态为失败，记录错误信息）
            $this->saveSendHistory(
                $mailData,
                $renderedHtml,
                ComplaintEnterpriseSendHistory::SEND_STATUS_FAILED,
                $e->getMessage()
            );

            throw new \Exception($msg);
        }
    }

    /**
     * 处理任务失败
     *
     * @param Throwable $e
     * @return void
     */
    public function failed(Throwable $e)
    {
        Log::error('[企业类举报邮件发送失败]', [
            'params' => $this->params ?? [],
            'attempt' => $this->attempts(),
            'msg' => $e->getMessage()
        ]);

        // 这里可以添加失败通知逻辑，比如发送邮件、钉钉通知等
    }

    /**
     * 获取任务标签，用于 Horizon 监控
     *
     * @return array
     */
    public function tags(): array
    {
        return [
            'complaint-enterprise',
            'complaint_id:' . $this->params['complaint_id'],
            'recipient_email:' . $this->params['recipient_email']
        ];
    }

    /**
     * 验证任务参数
     *
     * 检查必需的参数是否存在：
     * - complaint_id: 举报记录ID（必需）
     * - recipient_email: 收件人邮箱（必需）
     * - operator_id: 操作人ID（可选，默认0）
     * - operator_name: 操作人姓名（可选，默认'系统'）
     *
     * @throws \Exception 参数缺失时抛出异常
     */
    protected function validateParams()
    {
        // 验证必需参数
        if (empty($this->params['complaint_id']) || !isset($this->params['recipient_email'])) {
            throw new \Exception('队列事件缺少参数');
        }

        // 设置操作人信息默认值（如果未提供）
        if (!isset($this->params['operator_id'])) {
            $this->params['operator_id'] = 0;
        }
        if (!isset($this->params['operator_name'])) {
            $this->params['operator_name'] = '系统';
        }
    }

    /**
     * 渲染邮件HTML内容
     *
     * 使用 Laravel 的 View::make()->render() 方法渲染邮件模板，
     * 生成完整的HTML字符串用于保存到历史记录
     * 根据 useDouyinTemplate 参数选择不同的邮件模板：
     * - true: 使用抖音专用模板 emails.complaint_enterprise_douyin
     * - false: 使用默认模板 emails.complaint_enterprise
     *
     * @param array $mailData 邮件数据
     * @param bool $useDouyinTemplate 是否使用抖音模板，默认 false
     * @return string 渲染后的HTML内容，渲染失败返回空字符串
     */
    protected function renderMailHtml(array $mailData, bool $useDouyinTemplate = false): string
    {
        try {
            // 根据参数选择邮件模板视图
            // 抖音模板: resources/views/emails/complaint_enterprise_douyin.blade.php
            // 默认模板: resources/views/emails/complaint_enterprise.blade.php
            $viewName = $useDouyinTemplate
                ? 'emails.complaint_enterprise_douyin'
                : 'emails.complaint_enterprise';

            // 使用 View::make() 渲染邮件模板
            return View::make($viewName, ['data' => $mailData])->render();
        } catch (\Exception $e) {
            // 渲染失败，记录错误日志并返回空字符串
            Log::channel('job')->warning('[企业类举报邮件HTML渲染失败]', [
                'complaint_id' => $this->params['complaint_id'] ?? null,
                'use_douyin_template' => $useDouyinTemplate,
                'error_message' => $e->getMessage(),
            ]);
            return '';
        }
    }

    /**
     * 保存邮件发送历史记录
     *
     * 将邮件发送的详细信息保存到 complaint_enterprise_send_history 表，
     * 包括操作人信息、邮件数据、渲染后的HTML内容和发送状态
     *
     * 注意：历史记录保存失败不会影响主流程，仅记录错误日志
     *
     * @param array $mailData 邮件数据（JSON格式存储）
     * @param string $renderedHtml 渲染后的HTML内容
     * @param int $sendStatus 发送状态：1-成功，2-失败
     * @param string|null $errorMessage 失败时的错误信息
     * @return void
     */
    protected function saveSendHistory(
        array $mailData,
        string $renderedHtml,
        int $sendStatus,
        ?string $errorMessage = null
    ): void {
        try {
            // 创建历史记录
            ComplaintEnterpriseSendHistory::create([
                'complaint_id' => (int)$this->params['complaint_id'],
                'recipient_email' => $this->params['recipient_email'],
                'operator_id' => (int)($this->params['operator_id'] ?? 0),
                'operator_name' => $this->params['operator_name'] ?? '系统',
                'mail_data' => $mailData,
                'rendered_html' => $renderedHtml,
                'send_status' => $sendStatus,
                'error_message' => $errorMessage,
            ]);

            // 记录保存成功日志
            Log::channel('job')->info('[企业类举报邮件发送历史记录保存成功]', [
                'complaint_id' => $this->params['complaint_id'],
                'recipient_email' => $this->params['recipient_email'],
                'send_status' => $sendStatus,
            ]);
        } catch (\Exception $e) {
            // 历史记录保存失败，记录错误日志但不影响主流程
            Log::channel('job')->error('[企业类举报邮件发送历史记录保存失败]', [
                'complaint_id' => $this->params['complaint_id'] ?? null,
                'recipient_email' => $this->params['recipient_email'] ?? null,
                'send_status' => $sendStatus,
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}
