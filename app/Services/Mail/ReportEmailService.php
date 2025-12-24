<?php

namespace App\Services\Mail;

use App\Exceptions\ApiException;
use App\Mail\ReportMailTest\ReportEmailTestMail;
use App\Models\Mail\ReportEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * 举报邮箱服务类
 */
class ReportEmailService
{
    /**
     * 列表
     *
     * @param array $params
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function list(array $params = [])
    {

        $pageNum = (int)($params['pageNum'] ?? 1);
        $pageSize = (int)($params['pageSize'] ?? 10);

        return ReportEmail::query()
            // 邮箱-模糊搜索
            ->when(isset($params['email']) && $params['email'] != '', function ($q) use ($params) {
                $q->where('email', 'like', '%' . $params['email'] . '%');
            })
            ->orderByDesc('id')
            ->paginate($pageSize, ['*'], 'page', $pageNum);
    }

    /**
     * 详情
     *
     * @param int $id
     * @return Builder|Builder[]|Collection|Model
     * @throws ApiException
     */
    public function find(int $id)
    {
        // 1. 先查任务表
        $item = ReportEmail::query()->find($id);

        if (empty($item)) {
            throw new ApiException('记录不存在');
        }

        return $item;
    }

    /**
     * 创建
     *
     * @param array $data
     * @return Builder|Model
     * @throws ApiException
     */
    public function create(array $data)
    {
        try {
            return ReportEmail::query()->create($data);
        } catch (\Exception $e) {
            $msg = '创建邮箱配置失败: ' . $e->getMessage();
            Log::channel('daily')->error($msg, ['data' => $data ?? []]);
            throw new ApiException($msg);
        }
    }

    /**
     * 更新
     *
     * @param int $id
     * @param array $data
     * @return void
     * @throws ApiException
     */
    public function update(int $id, array $data)
    {
        $record = ReportEmail::query()->find($id);

        if (empty($record)) {
            throw new ApiException('记录不存在');
        }
        try {
            $record->update($data);
        } catch (\Exception $e) {
            $msg = '更新邮箱配置失败: ' . $e->getMessage();
            Log::channel('daily')->error($msg, ['id' => $id ?? 0, 'data' => $data ?? []]);
            throw new ApiException($msg);
        }
    }

    /**
     * 删除
     *
     * @param int $id
     * @return void
     * @throws ApiException
     */
    public function delete(int $id)
    {
        $record = ReportEmail::query()->find($id);

        if (empty($record)) {
            throw new ApiException('记录不存在');
        }
        try {
            $record->delete();
        } catch (\Exception $e) {
            $msg = '删除邮箱配置失败: ' . $e->getMessage();
            Log::channel('daily')->error($msg, ['id' => $id ?? 0]);
            throw new ApiException($msg);
        }
    }

    /**
     * 根据邮箱获取配置信息
     *
     * @param string $email
     * @return Builder|Model|object|null
     */
    public function getConfigByEmail(string $email)
    {
        return ReportEmail::query()
            ->where('email', $email)
            ->where('status', 1)
            ->first();
    }

    /**
     * 应用邮箱配置到 Laravel 邮件系统
     *
     * @param array $config
     * @return void
     */
    public function applyMailConfig(array $config)
    {
        Config::set('mail.mailers.smtp.host', $config['smtp_host'] ?? '');
        Config::set('mail.mailers.smtp.port', $config['smtp_port'] ?? '');
        Config::set('mail.mailers.smtp.username', $config['email'] ?? '');
        Config::set('mail.mailers.smtp.password', $config['auth_code'] ?? '');
        Config::set('mail.mailers.smtp.encryption', $config['smtp_port'] == 465 ? 'ssl' : 'tls');
        Config::set('mail.from.address', $config['email'] ?? '');
    }

    /**
     * 获取所有邮箱配置列表
     *
     * @return array
     */
    public function commonList(): array
    {
        return ReportEmail::query()
            ->select(['id', 'email'])
            // ->where('status', 1)
            ->get()
            ->toArray();
    }

    /**
     * 发送测试邮件
     *
     * @param int $id 邮箱配置ID
     * @param string $receiveEmail 收件人邮箱地址
     * @return void
     * @throws ApiException
     */
    public function sendTest(int $id, string $receiveEmail)
    {
        // 获取邮箱配置
        $config = $this->find($id);
        // 应用邮箱配置
        $this->applyMailConfig($config->toArray());

        // 准备测试邮件数据
        $mailData = [
            'email' => $config->email,
            'smtp_host' => $config->smtp_host,
            'smtp_port' => $config->smtp_port,
            'test_time' => now()->format('Y-m-d H:i:s'),
        ];

        try {
            // 发送测试邮件（同步发送，不走队列）
            Mail::to($receiveEmail)
                ->send(new ReportEmailTestMail($mailData));

            Log::channel('daily')->info('测试邮件发送成功', [
                'config_id' => $id,
                'from_email' => $config->email,
                'to_email' => $receiveEmail,
            ]);
        } catch (\Exception $e) {
            $msg = '测试邮件发送失败: ' . $e->getMessage();
            Log::channel('daily')->error($msg, [
                'config_id' => $id,
                'from_email' => $config->email,
                'to_email' => $receiveEmail,
                'error' => $e->getMessage(),
            ]);
            throw new ApiException($msg);
        }
    }
}
