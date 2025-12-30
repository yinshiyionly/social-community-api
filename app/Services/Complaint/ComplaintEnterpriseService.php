<?php

declare(strict_types=1);

namespace App\Services\Complaint;

use App\Exceptions\ApiException;
use App\Mail\Complaint\ComplaintEnterpriseMail;
use App\Models\Mail\ReportEmail;
use App\Models\PublicRelation\ComplaintEnterprise;
use App\Models\PublicRelation\MaterialEnterprise;
use App\Services\FileUploadService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * 企业投诉服务类
 *
 * 负责企业投诉的业务逻辑处理，包括CRUD操作、URL解析和材料字段处理
 */
class ComplaintEnterpriseService
{
    /**
     * 文件上传服务实例
     *
     * @var FileUploadService
     */
    private FileUploadService $fileUploadService;

    /**
     * 材料字段名称列表
     *
     * @var array
     */
    public const MATERIAL_FIELDS = [
        'enterprise_material',
        'contact_material',
        'report_material',
        'proof_material',
    ];

    /**
     * 构造函数
     *
     * @param FileUploadService $fileUploadService 文件上传服务
     */
    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * 解析详细举报网址（逗号或换行分割转JSON数组）
     *
     * @param string $urls URL字符串，以逗号或换行分割
     * @return array 格式: [{"url": "xxx"}, {"url": "yyy"}]
     */
    public function parseItemUrls(string $urls): array
    {
        if (empty(trim($urls))) {
            return [];
        }

        // 将换行符统一转换为逗号，然后按逗号分割
        $urls = str_replace(["\r\n", "\r", "\n"], ',', $urls);
        $urlArray = explode(',', $urls);

        $result = [];
        foreach ($urlArray as $url) {
            $url = trim($url);
            if (!empty($url)) {
                $result[] = ['url' => $url];
            }
        }

        return $result;
    }

    /**
     * 获取企业投诉分页列表
     *
     * @param array $params 查询参数 (site_name, human_name, report_state, pageNum, pageSize)
     * @return LengthAwarePaginator
     */
    public function getList(array $params): LengthAwarePaginator
    {
        $pageNum = (int)($params['pageNum'] ?? 1);
        $pageSize = (int)($params['pageSize'] ?? 10);

        return ComplaintEnterprise::query()
            // 举报网站名称-模糊搜索
            ->when(isset($params['site_name']) && $params['site_name'] !== '', function ($q) use ($params) {
                $q->where('site_name', 'like', '%' . $params['site_name'] . '%');
            })
            // 举报人-模糊搜索
            // todo 现在是从冗余字段查询 后续是否从关联模型表查询
            ->when(isset($params['human_name']) && $params['human_name'] !== '', function ($q) use ($params) {
                $q->where('human_name', 'like', '%' . $params['human_name'] . '%');
            })
            // 举报状态-精确匹配
            ->when(isset($params['report_state']) && $params['report_state'] !== '', function ($q) use ($params) {
                $q->where('report_state', $params['report_state']);
            })
            ->orderBy('id', 'desc')
            ->paginate($pageSize, ['*'], 'page', $pageNum);
    }

    /**
     * 根据ID获取企业投诉详情
     *
     * @param int $id 投诉记录ID
     * @return ComplaintEnterprise
     * @throws ApiException
     */
    public function getById(int $id): ComplaintEnterprise
    {
        $item = ComplaintEnterprise::find($id);

        if (!$item) {
            throw new ApiException('记录不存在');
        }

        return $item;
    }

    /**
     * 创建企业投诉记录
     *
     * @param array $data 投诉数据
     * @return ComplaintEnterprise
     * @throws ApiException
     */
    public function create(array $data): ComplaintEnterprise
    {
        // 根据举报人 material_id 去 material_enterprise 数据表查询资料
        $data = $this->getMaterialDataByMaterialId($data);

        // 设置默认举报类型
        $data['report_type'] = ComplaintEnterprise::REPORT_TYPE_ENTERPRISE;

        // 设置默认举报状态
        if (!isset($data['report_state'])) {
            $data['report_state'] = ComplaintEnterprise::REPORT_STATE_PLATFORM_REVIEWING;
        }
        try {
            return ComplaintEnterprise::create($data);
        } catch (\Exception $e) {
            $msg = '保存数据库失败: ' . $e->getMessage();
            Log::channel('daily')->error($msg, ['data' => $data]);
            throw new ApiException($msg);
        }
    }

    /**
     * 更新企业投诉记录
     *
     * @param int $id 投诉记录ID
     * @param array $data 更新数据
     * @return bool
     * @throws ApiException
     */
    public function update(int $id, array $data): bool
    {
        $item = $this->getById($id);

        // 根据举报人 material_id 去 material_enterprise 数据表查询资料
        $data = $this->getMaterialDataByMaterialId($data);
        try {
            return $item->update($data);
        } catch (\Exception $e) {
            $msg = '更新数据库失败: ' . $e->getMessage();
            Log::channel('daily')->error($msg, ['data' => $data]);
            throw new ApiException($msg);
        }
    }

    /**
     * 删除企业投诉记录（软删除）
     *
     * @param int $id 投诉记录ID
     * @return bool
     * @throws ApiException
     */
    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $item = $this->getById($id);

            return $item->delete();
        });
    }

    /**
     * 处理材料字段URL用于存储（移除schema和host，只保留path）
     *
     * @param array $data 包含材料字段的数据
     * @return array 处理后的数据
     */
    public function processMaterialUrlsForStorage(array $data): array
    {
        foreach (self::MATERIAL_FIELDS as $field) {
            if (!isset($data[$field]) || !is_array($data[$field])) {
                continue;
            }

            $materials = [];
            foreach ($data[$field] as $material) {
                if (!is_array($material) || !isset($material['url'])) {
                    continue;
                }

                $materials[] = [
                    'name' => $material['name'] ?? '',
                    'url' => $this->fileUploadService->extractPathFromUrl($material['url']),
                ];
            }

            $data[$field] = $materials;
        }

        return $data;
    }

    /**
     * 处理材料字段URL用于展示（拼接完整的schema和host）
     *
     * @param ComplaintEnterprise $model 企业投诉模型
     * @return ComplaintEnterprise 处理后的模型
     */
    public function processMaterialUrlsForDisplay(ComplaintEnterprise $model): ComplaintEnterprise
    {
        foreach (self::MATERIAL_FIELDS as $field) {
            $materials = $model->{$field};

            if (empty($materials) || !is_array($materials)) {
                $model->{$field} = [];
                continue;
            }

            $processedMaterials = [];
            foreach ($materials as $material) {
                if (!is_array($material) || !isset($material['url'])) {
                    continue;
                }

                $processedMaterials[] = [
                    'name' => $material['name'] ?? '',
                    'url' => $this->fileUploadService->generateFileUrl($material['url']),
                ];
            }

            $model->{$field} = $processedMaterials;
        }

        return $model;
    }

    /**
     * 获取证据种类枚举列表
     *
     * @return array
     */
    public function getProofTypes(): array
    {
        return ComplaintEnterprise::PROOF_TYPE_OPTIONS;
    }

    /**
     * 获取可用发件邮箱列表
     *
     * @return array
     */
    public function getReportEmails(): array
    {
        return ReportEmail::query()
            ->select(['id', 'email', 'name'])
            ->get()
            ->toArray();
    }

    /**
     * 根据 material_id 获取举报人材料并处理好
     *
     * @param array $data
     * @return array
     * @throws ApiException
     */
    public function getMaterialDataByMaterialId(array $data): array
    {
        $materialData = MaterialEnterprise::query()
            ->select(['enterprise_material', 'contact_material', 'contact_name'])
            ->where('id', $data['material_id'])
            ->where('status', MaterialEnterprise::STATUS_ENABLED)
            ->first();
        if (empty($materialData)) {
            throw new ApiException('未找到该举报人的资料');
        }
        $data['enterprise_material'] = $materialData['enterprise_material'] ?? [];
        $data['contact_material'] = $materialData['contact_material'] ?? [];
        // todo 不由 material_enterprise 数据表带入, 由用户自己上传
        // $data['report_material'] = $materialData['report_material'] ?? [];
        // $data['proof_material'] = $materialData['proof_material'] ?? [];
        $data['human_name'] = $materialData['contact_name'] ?? '';

        // 处理材料字段URL（移除schema和host）
        return $this->processMaterialUrlsForStorage($data);
    }

    /**
     * 收集附件文件路径（从TOS云存储下载到临时目录）
     *
     * 收集 enterprise_material、contact_material、report_material、proof_material 的文件路径，
     * 调用 downloadToTemp 从云存储下载文件到临时目录，
     * 下载失败则记录日志并跳过，
     * 返回附件路径数组（包含临时文件路径）
     *
     * @param ComplaintEnterprise $complaint 举报记录
     * @return array 附件路径数组，格式: [['path' => '/tmp/complaint_attachments/xxx.pdf', 'name' => '文件名.pdf', 'mime' => 'application/pdf'], ...]
     */
    protected function collectAttachmentPaths(ComplaintEnterprise $complaint): array
    {
        $attachmentPaths = [];

        // 遍历所有材料字段（enterprise_material、contact_material、report_material、proof_material）
        foreach (self::MATERIAL_FIELDS as $field) {
            $materials = $complaint->{$field};

            // 跳过空值或非数组
            if (empty($materials) || !is_array($materials)) {
                continue;
            }

            // 遍历每个材料文件
            foreach ($materials as $material) {
                // 跳过无效的材料数据
                if (!is_array($material) || empty($material['url'])) {
                    continue;
                }

                // 获取云存储相对路径和文件名
                $storagePath = $material['url'];
                $fileName = $material['name'] ?? basename($storagePath);

                // 调用 downloadToTemp 从云存储下载文件到临时目录
                $tempFilePath = $this->downloadToTemp($storagePath, $fileName);

                // 下载失败则记录日志并跳过
                if ($tempFilePath === null) {
                    Log::channel('daily')->warning('附件文件下载失败，跳过该附件', [
                        'complaint_id' => $complaint->id,
                        'field' => $field,
                        'storage_path' => $storagePath,
                        'file_name' => $fileName,
                    ]);
                    continue;
                }

                // 获取文件的MIME类型
                $mimeType = $this->fileUploadService->getMimeTypeByExtension(pathinfo($storagePath, PATHINFO_EXTENSION));

                // 添加到附件路径数组
                $attachmentPaths[] = [
                    'path' => $tempFilePath,  // 临时文件路径
                    'name' => $fileName,       // 原始文件名
                    'mime' => $mimeType,       // MIME类型
                ];
            }
        }

        // 记录收集附件完成日志
        Log::channel('daily')->info('收集附件文件完成', [
            'complaint_id' => $complaint->id,
            'attachment_count' => count($attachmentPaths),
        ]);

        return $attachmentPaths;
    }

    /**
     * 从TOS云存储下载文件到临时目录
     *
     * 使用 Storage::disk('volcengine')->get() 从TOS云存储获取文件内容，
     * 创建临时目录 /tmp/complaint_attachments/，
     * 将文件内容写入临时目录，
     * 返回临时文件路径，下载失败返回null并记录日志
     *
     * @param string $storagePath 云存储相对路径
     * @param string $fileName 文件名
     * @return string|null 本地临时文件路径，下载失败返回null
     */
    protected function downloadToTemp(string $storagePath, string $fileName): ?string
    {
        // 定义临时目录路径
        $tempDir = '/tmp/complaint_attachments';

        try {
            // 创建临时目录（如果不存在）
            if (!is_dir($tempDir)) {
                if (!mkdir($tempDir, 0755, true)) {
                    Log::channel('daily')->error('创建临时目录失败', [
                        'temp_dir' => $tempDir,
                    ]);
                    return null;
                }
            }
            // todo 测试 TOS 文件地址
            // $storagePath = 'uploads/2025/12/17/0ca04b8c-f2e6-4e99-b26b-8d7fce487f88.jpg';
            // 从TOS云存储获取文件内容
            $fileContent = Storage::disk('volcengine')->get($storagePath);

            if ($fileContent === null) {
                Log::channel('daily')->warning('从云存储获取文件内容为空', [
                    'storage_path' => $storagePath,
                    'file_name' => $fileName,
                ]);
                return null;
            }

            // 生成唯一的临时文件名（避免文件名冲突）
            $uniqueFileName = uniqid() . '_' . $fileName;
            $tempFilePath = $tempDir . '/' . $uniqueFileName;

            // 将文件内容写入临时目录
            $bytesWritten = file_put_contents($tempFilePath, $fileContent);

            if ($bytesWritten === false) {
                Log::channel('daily')->error('写入临时文件失败', [
                    'storage_path' => $storagePath,
                    'file_name' => $fileName,
                    'temp_file_path' => $tempFilePath,
                ]);
                return null;
            }

            // 记录下载成功日志
            Log::channel('daily')->info('从云存储下载文件到临时目录成功', [
                'storage_path' => $storagePath,
                'file_name' => $fileName,
                'temp_file_path' => $tempFilePath,
                'file_size' => $bytesWritten,
            ]);

            return $tempFilePath;
        } catch (\Exception $e) {
            // 记录下载失败日志
            Log::channel('daily')->warning('附件文件下载失败', [
                'storage_path' => $storagePath,
                'file_name' => $fileName,
                'error_message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * 准备邮件数据
     *
     * 从 complaint_enterprise 表获取举报信息，
     * 通过 material_id 关联 material_enterprise 表获取企业和联系人信息，
     * 组装完整的邮件数据结构
     *
     * 注意：此方法改为 public 访问级别，以便 ComplaintEnterpriseSendMailJob 可以调用获取邮件数据，
     * 用于保存邮件发送历史记录
     *
     * @param ComplaintEnterprise $complaint 举报记录
     * @return array 邮件数据数组
     * @throws ApiException
     */
    public function prepareMailData(ComplaintEnterprise $complaint): array
    {
        // 通过 material_id 获取企业资料信息
        $materialEnterprise = MaterialEnterprise::query()
            ->where('id', $complaint->material_id)
            ->where('status', MaterialEnterprise::STATUS_ENABLED)
            ->first();

        if (empty($materialEnterprise)) {
            throw new ApiException('未找到该举报人的资料');
        }

        // 处理材料字段URL用于展示（拼接完整的schema和host）
        // todo Storage::disk('volcengine')->get($storagePath) 不需要预先处理好 schema 和 host
        // $complaint = $this->processMaterialUrlsForDisplay($complaint);

        // 组装邮件数据结构
        return [
            // 邮件主题
            'subject' => sprintf(
                "%s账号内容侵权撤稿请求—%s-联系方式%s",
                $complaint->site_name ?? '',
                $materialEnterprise->name ?? '',
                $materialEnterprise->contact_phone ?? '',
            ),

            // ==================== 举报信息（来自 complaint_enterprise 表）====================
            // 举报网站名称
            'site_name' => $complaint->site_name ?? '',
            // 举报账号名称
            'account_name' => $complaint->account_name ?? '',
            // 详细举报网址（数组格式：[{"url": "xxx"}, {"url": "yyy"}]）
            'item_url' => $complaint->item_url ?? [],
            // 具体举报内容
            'report_content' => $complaint->report_content ?? '',
            // 举报材料（数组格式：[{"name": "xxx", "url": "yyy"}]）
            'report_material' => $complaint->report_material ?? [],
            // 证据种类（数组格式）
            'proof_type' => $complaint->proof_type ?? [],
            // 证据材料（数组格式：[{"name": "xxx", "url": "yyy"}]）
            'proof_material' => $complaint->proof_material ?? [],

            // ==================== 举报人信息（来自 material_enterprise 表）====================
            // 企业名称
            'company_name' => $materialEnterprise->name ?? '',
            // 营业执照或组织机构代码证（数组格式：[{"name": "xxx", "url": "yyy"}]）
            'enterprise_material' => $complaint->enterprise_material ?? [],
            // 企业类型
            'company_type' => $materialEnterprise->type ?? '',
            // 企业性质
            'company_nature' => $materialEnterprise->nature ?? '',
            // 行业分类
            'company_industry' => $materialEnterprise->industry ?? '',
            // 联系人身份
            'company_contact_identity' => $materialEnterprise->contact_identity ?? '',
            // 联系人材料（数组格式：[{"name": "xxx", "url": "yyy"}]）
            'contact_material' => $complaint->contact_material ?? [],
            // 联系人姓名
            'company_contact_name' => $materialEnterprise->contact_name ?? '',
            // 有效联系电话
            'company_contact_phone' => $materialEnterprise->contact_phone ?? '',
            // 有效电子邮件
            'company_contact_email' => $materialEnterprise->contact_email ?? '',
        ];
    }

    /**
     * 清理临时附件文件
     *
     * 遍历附件路径数组，删除临时文件，
     * 记录清理日志
     *
     * @param array $attachmentPaths 附件路径数组，格式: [['path' => '/tmp/complaint_attachments/xxx.pdf', 'name' => '文件名.pdf', 'mime' => 'application/pdf'], ...]
     * @return void
     */
    protected function cleanupTempAttachments(array $attachmentPaths): void
    {
        // 如果附件路径数组为空，直接返回
        if (empty($attachmentPaths)) {
            return;
        }

        $cleanedCount = 0;
        $failedCount = 0;

        // 遍历附件路径数组，删除临时文件
        foreach ($attachmentPaths as $attachment) {
            // 跳过无效的附件数据
            if (!is_array($attachment) || empty($attachment['path'])) {
                continue;
            }

            $tempFilePath = $attachment['path'];
            $fileName = $attachment['name'] ?? basename($tempFilePath);

            try {
                // 检查文件是否存在
                if (file_exists($tempFilePath)) {
                    // 删除临时文件
                    if (unlink($tempFilePath)) {
                        $cleanedCount++;
                    } else {
                        $failedCount++;
                        Log::channel('daily')->warning('删除临时附件文件失败', [
                            'temp_file_path' => $tempFilePath,
                            'file_name' => $fileName,
                        ]);
                    }
                }
            } catch (\Exception $e) {
                $failedCount++;
                Log::channel('daily')->warning('清理临时附件文件异常', [
                    'temp_file_path' => $tempFilePath,
                    'file_name' => $fileName,
                    'error_message' => $e->getMessage(),
                ]);
            }
        }

        // 记录清理完成日志
        Log::channel('daily')->info('清理临时附件文件完成', [
            'total_count' => count($attachmentPaths),
            'cleaned_count' => $cleanedCount,
            'failed_count' => $failedCount,
        ]);
    }

    /**
     * 审核企业投诉
     *
     * 平台审核投诉记录，仅允许从"平台审核中"状态进行审核操作。
     * 审核通过后创建者才可以发送邮件请求。
     *
     * @param int $id 投诉记录ID
     * @param int $reportState 目标审核状态（2-平台驳回、3-平台审核通过、4-官方审核中）
     * @return bool 审核成功返回 true
     * @throws ApiException 记录不存在或状态不允许审核时抛出异常
     */
    public function audit(int $id, int $reportState): bool
    {
        // 获取投诉记录
        $complaint = $this->getById($id);

        // 校验当前状态是否允许审核（仅"平台审核中"状态可以进行审核操作）
        /*if ($complaint->report_state !== ComplaintEnterprise::REPORT_STATE_PLATFORM_REVIEWING) {
            throw new ApiException('当前状态不允许审核操作，仅"平台审核中"状态可以进行审核');
        }*/

        // 校验目标状态是否有效
        $allowedStates = [
            ComplaintEnterprise::REPORT_STATE_PLATFORM_REJECTED,   // 2-平台驳回
            ComplaintEnterprise::REPORT_STATE_PLATFORM_APPROVED,   // 3-平台审核通过
            ComplaintEnterprise::REPORT_STATE_OFFICIAL_REVIEWING,  // 4-官方审核中
        ];

        if (!in_array($reportState, $allowedStates, true)) {
            throw new ApiException('目标审核状态无效');
        }

        try {
            // 更新审核状态
            $result = $complaint->update(['report_state' => $reportState]);

            // 记录审核操作日志
            Log::channel('daily')->info('企业投诉审核操作成功', [
                'complaint_id' => $id,
                'old_state' => ComplaintEnterprise::REPORT_STATE_PLATFORM_REVIEWING,
                'new_state' => $reportState,
                'new_state_label' => ComplaintEnterprise::REPORT_STATE_LABELS[$reportState] ?? '未知',
            ]);

            return $result;
        } catch (\Exception $e) {
            $msg = '审核操作失败: ' . $e->getMessage();
            Log::channel('daily')->error($msg, [
                'complaint_id' => $id,
                'report_state' => $reportState,
            ]);
            throw new ApiException($msg);
        }
    }

    /**
     * 发送企业类举报邮件
     *
     * 调用 prepareMailData 准备邮件数据，
     * 调用 collectAttachmentPaths 收集附件（从云存储下载），
     * 使用 Mail::to() 发送邮件，
     * 无论成功或失败，都调用 cleanupTempAttachments 清理临时文件，
     * 处理发送异常
     *
     * 支持根据 useDouyinTemplate 参数选择不同的邮件模板：
     * - true: 使用抖音专用模板（针对 @bytedance.com 域名）
     * - false: 使用默认模板
     *
     * @param int $complaintId 举报记录ID
     * @param string $recipientEmail 收件人邮箱
     * @param bool $useDouyinTemplate 是否使用抖音专用模板，默认 false
     * @return bool 发送成功返回 true
     * @throws ApiException 记录不存在或邮件发送失败时抛出异常
     */
    public function sendEmail(int $complaintId, string $recipientEmail, bool $useDouyinTemplate = false): bool
    {
        // 获取举报记录
        $complaint = $this->getById($complaintId);

        // 初始化附件路径数组（用于finally块中清理）
        $attachmentPaths = [];

        try {
            // 调用 prepareMailData 准备邮件数据
            $mailData = $this->prepareMailData($complaint);

            // 调用 collectAttachmentPaths 收集附件（从云存储下载到临时目录）
            $attachmentPaths = $this->collectAttachmentPaths($complaint);

            // 创建邮件实例（根据 useDouyinTemplate 参数选择邮件模板）
            // 抖音模板用于 @bytedance.com 域名的收件人
            $mail = new ComplaintEnterpriseMail($mailData, $attachmentPaths, $useDouyinTemplate);

            // 使用 Mail::to() 发送邮件
            Mail::to($recipientEmail)->send($mail);

            // 记录发送成功日志（包含模板类型信息）
            Log::channel('daily')->info('企业类举报邮件发送成功', [
                'complaint_id' => $complaintId,
                'recipient_email' => $recipientEmail,
                'attachment_count' => count($attachmentPaths),
                'use_douyin_template' => $useDouyinTemplate,
                'template_type' => $useDouyinTemplate ? '抖音专用模板' : '默认模板',
            ]);

            return true;
        } catch (ApiException $e) {
            // 业务异常（如记录不存在、未找到举报人资料）直接抛出
            throw $e;
        } catch (\Exception $e) {
            // 记录邮件发送失败错误日志
            Log::channel('daily')->error('企业类举报邮件发送失败', [
                'complaint_id' => $complaintId,
                'recipient_email' => $recipientEmail,
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
                'use_douyin_template' => $useDouyinTemplate,
            ]);

            // 抛出邮件发送失败异常
            throw new ApiException('邮件发送失败: ' . $e->getMessage());
        } finally {
            // 无论成功或失败，都调用 cleanupTempAttachments 清理临时文件
            // 确保临时下载的附件文件被清理，避免占用磁盘空间
            $this->cleanupTempAttachments($attachmentPaths);
        }
    }
}
