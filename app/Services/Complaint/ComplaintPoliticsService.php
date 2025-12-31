<?php

declare(strict_types=1);

namespace App\Services\Complaint;

use App\Exceptions\ApiException;
use App\Mail\Complaint\ComplaintPoliticsMail;
use App\Models\Mail\ReportEmail;
use App\Models\PublicRelation\ComplaintPolitics;
use App\Models\PublicRelation\MaterialPolitics;
use App\Services\FileUploadService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * 政治类投诉服务类
 *
 * 负责政治类投诉的业务逻辑处理，包括CRUD操作、枚举值获取和材料字段处理
 */
class ComplaintPoliticsService
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
        'report_material',
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
     * 获取政治类投诉分页列表
     *
     * 支持按 site_name/app_name/account_name/human_name/report_state 筛选
     * 按ID倒序返回分页结果
     *
     * @param array $params 查询参数 (site_name, app_name, account_name, human_name, report_state, pageNum, pageSize)
     * @return LengthAwarePaginator
     */
    public function getList(array $params): LengthAwarePaginator
    {
        $pageNum = (int)($params['pageNum'] ?? 1);
        $pageSize = (int)($params['pageSize'] ?? 10);

        return ComplaintPolitics::query()
            // 网站名称-模糊搜索
            ->when(isset($params['site_name']) && $params['site_name'] !== '', function ($q) use ($params) {
                $q->where('site_name', 'like', '%' . $params['site_name'] . '%');
            })
            // APP名称-模糊搜索
            ->when(isset($params['app_name']) && $params['app_name'] !== '', function ($q) use ($params) {
                $q->where('app_name', 'like', '%' . $params['app_name'] . '%');
            })
            // 账号名称-模糊搜索
            ->when(isset($params['account_name']) && $params['account_name'] !== '', function ($q) use ($params) {
                $q->where('account_name', 'like', '%' . $params['account_name'] . '%');
            })
            // 举报人-模糊搜索
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
     * 根据ID获取政治类投诉详情
     *
     * @param int $id 投诉记录ID
     * @return ComplaintPolitics
     * @throws ApiException 当记录不存在时抛出异常
     */
    public function getById(int $id): ComplaintPolitics
    {
        $item = ComplaintPolitics::find($id);

        if (!$item) {
            throw new ApiException('记录不存在');
        }

        return $item;
    }

    /**
     * 创建政治类投诉记录
     *
     * @param array $data 投诉数据
     * @return ComplaintPolitics
     * @throws ApiException 当创建失败时抛出异常
     */
    public function create(array $data): ComplaintPolitics
    {
        // 根据举报人 material_id 去 material_politics 数据表查询资料
        $data = $this->getMaterialDataByMaterialId($data);

        // 设置默认举报类型为政治类
        $data['report_type'] = ComplaintPolitics::REPORT_TYPE_POLITICS;

        // 设置默认举报状态为平台审核中
        if (!isset($data['report_state'])) {
            $data['report_state'] = ComplaintPolitics::REPORT_STATE_PLATFORM_REVIEWING;
        }

        try {
            return ComplaintPolitics::create($data);
        } catch (\Exception $e) {
            $msg = '保存数据库失败: ' . $e->getMessage();
            Log::channel('daily')->error($msg, ['data' => $data]);
            throw new ApiException($msg);
        }
    }

    /**
     * 更新政治类投诉记录
     *
     * @param int $id 投诉记录ID
     * @param array $data 更新数据
     * @return bool
     * @throws ApiException 当记录不存在或更新失败时抛出异常
     */
    public function update(int $id, array $data): bool
    {
        $item = $this->getById($id);

        // 根据举报人 material_id 去 material_politics 数据表查询资料
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
     * 删除政治类投诉记录（软删除）
     *
     * @param int $id 投诉记录ID
     * @return bool
     * @throws ApiException 当记录不存在时抛出异常
     */
    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $item = $this->getById($id);

            return $item->delete();
        });
    }

    /**
     * 根据 material_id 获取举报人材料并处理好
     *
     * @param array $data 包含 material_id 的数据
     * @return array 处理后的数据
     * @throws ApiException 当举报人资料不存在时抛出异常
     */
    public function getMaterialDataByMaterialId(array $data): array
    {
        $materialData = MaterialPolitics::query()
            ->select(['report_material', 'name'])
            ->where('id', $data['material_id'])
            ->where('status', MaterialPolitics::STATUS_ENABLED)
            ->first();

        if (empty($materialData)) {
            throw new ApiException('未找到该举报人的资料');
        }

        // 从政治类资料表获取举报材料
        // todo 不由 material_politics 数据表带入, 由用户自己上传
        // $data['report_material'] = $materialData['report_material'] ?? [];

        // 设置举报人姓名
        $data['human_name'] = $materialData['name'] ?? '';

        // 处理材料字段URL（移除schema和host）
        return $this->processMaterialUrlsForStorage($data);
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
     * @param ComplaintPolitics $model 政治类投诉模型
     * @return ComplaintPolitics 处理后的模型
     */
    public function processMaterialUrlsForDisplay(ComplaintPolitics $model): ComplaintPolitics
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
     * 获取危害小类枚举列表
     *
     * @return array
     */
    public function getReportSubTypes(): array
    {
        return ComplaintPolitics::REPORT_SUB_TYPE_OPTIONS;
    }

    /**
     * 获取被举报平台枚举列表
     *
     * @return array
     */
    public function getReportPlatforms(): array
    {
        return ComplaintPolitics::REPORT_PLATFORM_OPTIONS;
    }

    /**
     * 获取APP定位枚举列表
     *
     * @return array
     */
    public function getAppLocations(): array
    {
        return ComplaintPolitics::APP_LOCATION_OPTIONS;
    }

    /**
     * 获取账号平台枚举列表
     *
     * @return array
     */
    public function getAccountPlatforms(): array
    {
        return ComplaintPolitics::ACCOUNT_PLATFORM_OPTIONS;
    }

    /**
     * 根据账号平台获取账号性质枚举列表
     *
     * @param string $platform 账号平台
     * @return array
     */
    public function getAccountNatures(string $platform): array
    {
        return ComplaintPolitics::getAccountNatureOptions($platform);
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
     * 收集附件文件路径（从TOS云存储下载到临时目录）
     *
     * 收集 report_material 的文件路径，
     * 调用 downloadToTemp 从云存储下载文件到临时目录，
     * 下载失败则记录日志并跳过，
     * 返回附件路径数组（包含临时文件路径）
     *
     * @param ComplaintPolitics $complaint 举报记录
     * @return array 附件路径数组，格式: [['path' => '/tmp/complaint_attachments/xxx.pdf', 'name' => '文件名.pdf', 'mime' => 'application/pdf'], ...]
     */
    protected function collectAttachmentPaths(ComplaintPolitics $complaint): array
    {
        $attachmentPaths = [];

        // 遍历所有材料字段（report_material）
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
     * 从 complaint_politics 表获取举报信息，
     * 通过 material_id 关联 material_politics 表获取举报人信息，
     * 根据 report_platform 动态组装不同平台的数据，
     * 组装完整的邮件数据结构
     *
     * @param ComplaintPolitics $complaint 举报记录
     * @return array 邮件数据数组
     * @throws ApiException
     */
    public function prepareMailData(ComplaintPolitics $complaint): array
    {
        // 通过 material_id 获取举报人资料信息
        $materialPolitics = MaterialPolitics::query()
            ->where('id', $complaint->material_id)
            ->where('status', MaterialPolitics::STATUS_ENABLED)
            ->first();

        // 举报人资料不存在则抛出异常
        if (empty($materialPolitics)) {
            throw new ApiException('未找到该举报人的资料');
        }

        // 根据 report_platform 获取平台名称用于邮件主题
        $platformName = $this->getPlatformNameForSubject($complaint);

        // 组装邮件数据结构
        $mailData = [
            // ==================== 邮件基础信息 ====================
            // 邮件主题（根据被举报平台动态生成）
            'subject' => sprintf(
                "%s账号内容侵权撤稿请求—联系人%s-联系方式%s",
                $platformName ?? '',
                $materialPolitics->name ?? '',
                $materialPolitics->contact_phone ?? ''
            ),
            // 邮件日期
            'date' => date('Y年m月d日'),

            // ==================== 举报人信息（来自 material_politics 表）====================
            // 举报人姓名
            'human_name' => $materialPolitics->name ?? '',
            // 举报人性别（转换为中文标签）
            'human_gender' => MaterialPolitics::GENDER_LABELS[$materialPolitics->gender] ?? '未知',
            // 举报人有效联系电话
            'human_phone' => $materialPolitics->contact_phone ?? '',
            // 举报人有效电子邮件
            'human_email' => $materialPolitics->contact_email ?? '',
            // 举报人通讯地址
            'human_address' => $materialPolitics->contact_address ?? '',

            // ==================== 举报类型信息（来自 complaint_politics 表）====================
            // 举报类型（固定为"政治类"）
            'report_type' => $complaint->report_type ?? ComplaintPolitics::REPORT_TYPE_POLITICS,
            // 危害小类（重要：不能遗漏）
            'report_sub_type' => $complaint->report_sub_type ?? '',
            // 被举报平台（决定邮件模版渲染哪种平台信息）
            'report_platform' => $complaint->report_platform ?? '',

            // ==================== 举报内容 ====================
            // 具体举报内容
            'report_content' => $complaint->report_content ?? '',

            // ==================== 附件信息（用于邮件模版展示附件列表）====================
            // 举报材料（数组格式：[{"name": "xxx", "url": "yyy"}]）
            'attachments' => $complaint->report_material ?? [],
        ];

        // 根据被举报平台类型添加对应的平台信息
        $mailData = $this->appendPlatformData($mailData, $complaint);

        return $mailData;
    }

    /**
     * 根据被举报平台获取平台名称（用于邮件主题）
     *
     * 根据 report_platform 字段值返回对应的平台名称：
     * - 网站网页：返回 site_name
     * - APP：返回 app_name
     * - 网络账号：返回 account_name
     *
     * @param ComplaintPolitics $complaint 举报记录
     * @return string 平台名称
     */
    protected function getPlatformNameForSubject(ComplaintPolitics $complaint): string
    {
        switch ($complaint->report_platform) {
            case ComplaintPolitics::REPORT_PLATFORM_WEBSITE:
                // 网站网页类型：使用网站名称
                return $complaint->site_name ?? '未知网站';

            case ComplaintPolitics::REPORT_PLATFORM_APP:
                // APP类型：使用APP名称
                return $complaint->app_name ?? '未知APP';

            case ComplaintPolitics::REPORT_PLATFORM_ACCOUNT:
                // 网络账号类型：使用账号名称
                return $complaint->account_name ?? '未知账号';

            default:
                return '未知平台';
        }
    }

    /**
     * 根据被举报平台类型追加对应的平台数据
     *
     * 根据 report_platform 字段值追加不同的平台信息：
     * - 网站网页：追加 site_name, site_url
     * - APP：追加 app_name, app_location, app_url
     * - 网络账号：追加 account_platform, account_platform_name, account_nature, account_name, account_url
     *
     * @param array $mailData 邮件数据
     * @param ComplaintPolitics $complaint 举报记录
     * @return array 追加平台数据后的邮件数据
     */
    protected function appendPlatformData(array $mailData, ComplaintPolitics $complaint): array
    {
        switch ($complaint->report_platform) {
            case ComplaintPolitics::REPORT_PLATFORM_WEBSITE:
                // ==================== 网站网页类型 ====================
                // 网站名称
                $mailData['site_name'] = $complaint->site_name ?? '';
                // 网站网址（数组格式：[{"url": "xxx"}, {"url": "yyy"}]）
                $mailData['site_url'] = $complaint->site_url ?? [];
                break;

            case ComplaintPolitics::REPORT_PLATFORM_APP:
                // ==================== APP类型 ====================
                // APP名称
                $mailData['app_name'] = $complaint->app_name ?? '';
                // APP定位（有害信息链接/APP官方网址/APP下载地址）
                $mailData['app_location'] = $complaint->app_location ?? '';
                // APP网址（数组格式：[{"url": "xxx"}, {"url": "yyy"}]）
                $mailData['app_url'] = $complaint->app_url ?? [];
                break;

            case ComplaintPolitics::REPORT_PLATFORM_ACCOUNT:
                // ==================== 网络账号类型 ====================
                // 账号平台（微信/QQ/微博/贴吧/博客/直播平台/论坛社区/网盘/音频/其他）
                $mailData['account_platform'] = $complaint->account_platform ?? '';
                // 账号平台名称（当账号平台为博客/直播平台/论坛社区/网盘/音频/其他时需要填写）
                $mailData['account_platform_name'] = $complaint->account_platform_name ?? '';
                // 账号性质（个人/公众/群组/认证/非认证，根据账号平台不同而不同）
                $mailData['account_nature'] = $complaint->account_nature ?? '';
                // 账号名称
                $mailData['account_name'] = $complaint->account_name ?? '';
                // 账号网址（数组格式：[{"url": "xxx"}, {"url": "yyy"}]）
                $mailData['account_url'] = $complaint->account_url ?? [];
                break;
        }

        return $mailData;
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
     * 审核政治类投诉
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
        /*if ($complaint->report_state !== ComplaintPolitics::REPORT_STATE_PLATFORM_REVIEWING) {
            throw new ApiException('当前状态不允许审核操作，仅"平台审核中"状态可以进行审核');
        }*/

        // 校验目标状态是否有效
        $allowedStates = [
            ComplaintPolitics::REPORT_STATE_PLATFORM_REJECTED,   // 2-平台驳回
            ComplaintPolitics::REPORT_STATE_PLATFORM_APPROVED,   // 3-平台审核通过
            ComplaintPolitics::REPORT_STATE_OFFICIAL_REVIEWING,  // 4-官方审核中
        ];

        if (!in_array($reportState, $allowedStates, true)) {
            throw new ApiException('目标审核状态无效');
        }

        try {
            // 更新审核状态
            $result = $complaint->update(['report_state' => $reportState]);

            // 记录审核操作日志
            Log::channel('daily')->info('政治类投诉审核操作成功', [
                'complaint_id' => $id,
                'old_state' => ComplaintPolitics::REPORT_STATE_PLATFORM_REVIEWING,
                'new_state' => $reportState,
                'new_state_label' => ComplaintPolitics::REPORT_STATE_LABELS[$reportState] ?? '未知',
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
     * 发送政治类举报邮件
     *
     * 调用 prepareMailData 准备邮件数据，
     * 调用 collectAttachmentPaths 收集附件（从云存储下载），
     * 使用 Mail::to() 发送邮件，
     * 无论成功或失败，都调用 cleanupTempAttachments 清理临时文件，
     * 处理发送异常
     *
     * 通过 templateName 参数指定使用的邮件模板视图名称，
     * 模板名称由 ComplaintEmailService::getPoliticsTemplateByEmail 根据收件人邮箱获取
     *
     * @param int $complaintId 举报记录ID
     * @param string $recipientEmail 收件人邮箱
     * @param string $templateName 邮件模板视图名称，如 'emails.complaint_politics'
     * @return bool 发送成功返回 true
     * @throws ApiException 记录不存在或邮件发送失败时抛出异常
     */
    public function sendEmail(int $complaintId, string $recipientEmail, string $templateName): bool
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

            // 创建邮件实例，传入模板名称参数
            // 模板名称由 ComplaintEmailService::getPoliticsTemplateByEmail 根据收件人邮箱获取
            $mail = new ComplaintPoliticsMail($mailData, $attachmentPaths, $templateName);

            // 使用 Mail::to() 发送邮件
            Mail::to($recipientEmail)->send($mail);

            // 记录发送成功日志（包含使用的模板名称）
            Log::channel('daily')->info('政治类举报邮件发送成功', [
                'complaint_id' => $complaintId,
                'recipient_email' => $recipientEmail,
                'report_platform' => $complaint->report_platform ?? '',
                'attachment_count' => count($attachmentPaths),
                'template_name' => $templateName,
            ]);

            return true;
        } catch (ApiException $e) {
            // 业务异常（如记录不存在、未找到举报人资料）直接抛出
            throw $e;
        } catch (\Exception $e) {
            // 记录邮件发送失败错误日志
            Log::channel('daily')->error('政治类举报邮件发送失败', [
                'complaint_id' => $complaintId,
                'recipient_email' => $recipientEmail,
                'report_platform' => $complaint->report_platform ?? '',
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
                'template_name' => $templateName,
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
