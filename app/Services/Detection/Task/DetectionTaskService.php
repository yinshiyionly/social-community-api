<?php

namespace App\Services\Detection\Task;

use App\Exceptions\ApiException;
use App\Helper\KeywordParser\BasedLocationParser;
use App\Helper\KeywordParser\KeywordRuleParser;
use App\Helper\KeywordParser\TagsParser;
use App\Helper\Volcengine\InsightAPI;
use App\Models\Detection\DetectionTaskMaster;
use App\Models\Insight\InsightPost;
use Illuminate\Support\Facades\Log;

class DetectionTaskService
{
    /**
     * 获取任务列表
     *
     * @param array $params
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getList(array $params)
    {
        $pageNum = (int)($params['pageNum'] ?? 1);
        $pageSize = (int)($params['pageSize'] ?? 10);

        return DetectionTaskMaster::query()
            // 文本关键词-模糊搜索
            ->when(isset($params['text_plain']) && $params['text_plain'] != '', function ($q) use ($params) {
                $q->where('text_plain', 'like', '%' . $params['text_plain'] . '%');
            })
            // 任务名称-模糊搜索
            ->when(isset($params['task_name']) && $params['task_name'] != '', function ($q) use ($params) {
                $q->where('task_name', 'like', '%' . $params['task_name'] . '%');
            })
            ->orderByDesc('task_id')
            ->paginate($pageSize, ['*'], 'page', $pageNum);
    }

    /**
     * 根据任务ID获取详情
     *
     * @param int $taskId
     * @return mixed
     * @throws ApiException
     */
    public function getById(int $taskId)
    {
        // 1. 先查任务表
        $item = DetectionTaskMaster::query()->find($taskId);

        if (empty($item)) {
            throw new ApiException('记录不存在');
        }

        // 2. 根据task_id查询数据表
        /*$postData = InsightPost::query()
            ->whereRaw("JSON_CONTAINS(matched_task_ids, CAST(? AS JSON), '$')", [$taskId])
            ->orderByDesc('publish_time')
            ->get();

        // 3. 将 postData 附加到 item
        $item->post_data = $postData;*/

        return $item;
    }


    /**
     * 创建任务
     *
     * @param array $data
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model
     * @throws \Exception
     */
    public function create(array $data)
    {
        # 1. 处理监测任务规则配置
        try {
            // 1. 使用 KeywordRuleParser 解析关键词并得到 textRule
            if (!empty($data['text_plain'])) {
                $textRule = $this->parseKeyword($data['text_plain']);
            }
            // 2. 使用 BasedLocationParser 解析地域并得到 basedLocationRule
            if (!empty($data['based_location_plain'])) {
                $basedLocationRule = $this->parsebasedLocation($data['based_location_plain']);
            }
            // 3. 使用 TagParser 解析行业标签并得到 tagRule
            if (!empty($data['tag_plain'])) {
                $tagRule = $this->parseTags($data['tag_plain']);
            }
            // 4. 将 basedLocationRule 和 tagRule 追加到 textRule 合并成新的 Rule
            $rule = [];
            if (!empty($textRule)) {
                $rule = $textRule;
            }
            if (!empty($basedLocationRule)) {
                $rule['rule'][2] = $basedLocationRule;
            }
            if (!empty($tagRule)) {
                $rule['rule'][3] = $tagRule;
            }

        } catch (\Exception $e) {
            $msg = '处理监测任务关键词规则错误: ' . $e->getMessage();
            $this->errorLog($msg, [
                'data' => $data,
                'text_rule' => $textRule ?? [],
                'based_location_rule' => $basedLocationRule ?? [],
                'tag_rule' => $tagRule ?? []
            ]);
            throw new \Exception($msg);
        }

        // todo 加入敏感词校验
        # 2. 过滤文本关键词中的敏感词

        # 3. 调用火山内容洞察接口创建实时任务
        $insightHelper = new InsightAPI();

        try {
            $createTaskResult = $insightHelper->bizSubCreateTask([
                'rule' => $rule['rule'],
                'sync_mode' => false
            ]);
            // todo mock 数据
            /*$createTaskResult = [
                'status' => 0,
                'message' => 'succeed',
                'data' => [
                    'task_id' => 9999
                ]
            ];*/
        } catch (\Exception $e) {
            $msg = '创建实时任务失败: ' . $e->getMessage();
            $this->errorLog($msg, ['data' => $data, 'create_task_result' => $createTaskResult ?? []]);
            throw new \Exception($msg);
        }

        # 4. 入库
        $insert = [
            'external_task_id' => $createTaskResult['data']['task_id'],
            'external_rule' => $rule['rule'],
            // todo 定时crontab任务获取状态
            'external_enable_status' => 0,
            //
            'external_sync_mode' => 'false',
            'task_name' => $data['task_name'],
            'text_rule' => $textRule['rule'] ?? [],
            'text_plain' => $data['text_plain'],
            'tag_rule' => $tagRule ?? [],
            'tag_plain' => $data['tag_plain'] ?? [],
            'based_location_rule' => $basedLocationRule ?? [],
            'based_location_plain' => $data['based_location_plain'] ?? [],
            'data_site' => $data['data_site'] ?? [],
            'warn_name' => $data['warn_name'] ?? '',
            'warn_reception_start_time' => $data['warn_reception_start_time'] ?? null,
            'warn_reception_end_time' => $data['warn_reception_end_time'] ?? null,
            'warn_publish_email_state' => $data['warn_publish_email_state'] ?? 2,
            'warn_publish_email_config' => $data['warn_publish_email_config'] ?? [],
            'warn_publish_wx_state' => $data['warn_publish_wx_state'] ?? 2,
            'warn_publish_wx_config' => $data['warn_publish_wx_config'] ?? []
        ];

        return DetectionTaskMaster::query()->create($insert);
    }

    /**
     * 更新任务
     *
     * @param int $taskId
     * @param array $data
     * @return void
     * @throws \Exception
     */
    public function update(int $taskId, array $data)
    {
        try {
            $originData = DetectionTaskMaster::query()->find($taskId);
            if (empty($originData)) {
                throw new \Exception('记录不存在');
            }
        } catch (\Exception $e) {
            $msg = '更新监测任务-获取任务详情失败: ' . $e->getMessage();
            $this->errorLog($msg, ['task_id' => $taskId]);
            throw new \Exception($msg);
        }

        # 1. 处理监测任务规则配置
        try {
            // 1. 使用 KeywordRuleParser 解析关键词并得到 textRule
            if (!empty($data['text_plain'])) {
                $textRule = $this->parseKeyword($data['text_plain']);
            }
            // 2. 使用 BasedLocationParser 解析地域并得到 basedLocationRule
            if (!empty($data['based_location_plain'])) {
                $basedLocationRule = $this->parsebasedLocation($data['based_location_plain']);
            }
            // 3. 使用 TagParser 解析行业标签并得到 tagRule
            if (!empty($data['tag_plain'])) {
                $tagRule = $this->parseTags($data['tag_plain']);
            }
            // 4. 将 basedLocationRule 和 tagRule 追加到 textRule 合并成新的 Rule
            $rule = [];
            if (!empty($textRule)) {
                $rule = $textRule;
            }
            if (!empty($basedLocationRule)) {
                $rule['rule'][2] = $basedLocationRule;
            }
            if (!empty($tagRule)) {
                $rule['rule'][3] = $tagRule;
            }

        } catch (\Exception $e) {
            $msg = '处理监测任务关键词规则错误: ' . $e->getMessage();
            $this->errorLog($msg, [
                'data' => $data,
                'text_rule' => $textRule ?? [],
                'based_location_rule' => $basedLocationRule ?? [],
                'tag_rule' => $tagRule ?? []
            ]);
            throw new \Exception($msg);
        }

        // todo 加入敏感词校验
        # 2. 过滤文本关键词中的敏感词

        # 3. 调用火山内容洞察接口创建实时任务
        $insightHelper = new InsightAPI();

        try {
            $updateTaskResult = $insightHelper->bizSubUpdateTask([
                'rule' => $rule['rule'],
                'enable_status' => $originData->external_enable_status,
                'task_id' => $originData->external_task_id,
                'sync_mode' => false
            ]);
            // todo mock 数据
            /*$updateTaskResult = [
                'status' => 0,
                'message' => 'succeed',
                'data' => [
                    'task_id' => 9999,
                    'enable_status' => 1
                ]
            ];*/
        } catch (\Exception $e) {
            $msg = '更新实时任务失败: ' . $e->getMessage();
            $this->errorLog($msg, ['data' => $data, 'update_task_result' => $updateTaskResult ?? []]);
            throw new \Exception($msg);
        }

        # 4. 入库
        $update = [
            'external_task_id' => $updateTaskResult['data']['task_id'],
            'external_rule' => $rule['rule'],
            // todo 定时crontab任务获取状态
            'external_enable_status' => $updateTaskResult['data']['enable_status'],
            'external_sync_mode' => 'false',
            'task_name' => $data['task_name'],
            'text_rule' => $textRule['rule'] ?? [],
            'text_plain' => $data['text_plain'],
            'tag_rule' => $tagRule ?? [],
            'tag_plain' => $data['tag_plain'] ?? [],
            'based_location_rule' => $basedLocationRule ?? [],
            'based_location_plain' => $data['based_location_plain'] ?? [],
            'data_site' => $data['data_site'] ?? [],
            'warn_name' => $data['warn_name'] ?? '',
            'warn_reception_start_time' => $data['warn_reception_start_time'] ?? null,
            'warn_reception_end_time' => $data['warn_reception_end_time'] ?? null,
            'warn_publish_email_state' => $data['warn_publish_email_state'] ?? 2,
            'warn_publish_email_config' => $data['warn_publish_email_config'] ?? [],
            'warn_publish_wx_state' => $data['warn_publish_wx_state'] ?? 2,
            'warn_publish_wx_config' => $data['warn_publish_wx_config'] ?? []
        ];

        try {
            // todo 使用 laravel model 的 dirty 检测机制, 只更新变更的字段
            // todo dirty 后的json数据会导致转义
            // $originData->fill($update);
            // $changed = $originData->getDirty(); // 只获取变化的字段
            // if (!empty($changed)) {
            //    $originData->update($changed);
            // }
            $originData->update($update);
        } catch (\Exception $e) {
            $msg = '更新监测任务失败: ' . $e->getMessage();
            $this->errorLog($msg, ['task_id' => $taskId, 'data' => $data]);
            throw new \Exception($msg);
        }
    }

    /**
     * 删除任务-软删除
     *
     * @param int $taskId
     * @return void
     * @throws \Exception
     */
    public function delete(int $taskId)
    {
        try {
            $originData = DetectionTaskMaster::query()->find($taskId);
            if (empty($originData)) {
                throw new \Exception('记录不存在');
            }
        } catch (\Exception $e) {
            $msg = '删除监测任务-获取任务详情失败: ' . $e->getMessage();
            $this->errorLog($msg, ['task_id' => $taskId]);
            throw new \Exception($msg);
        }
        // 1. 删除任务之前先关闭任务
        try {
            $this->closeAction($taskId);
        } catch (\Exception $e) {
            $msg = '删除监测任务-关闭任务失败: ' . $e->getMessage();
            $this->errorLog($msg, ['task_id' => $taskId]);
            throw new \Exception($msg);
        }
        // 2. 删除任务-软删除
        try {
            $originData->delete();
        } catch (\Exception $e) {
            $msg = '删除监测任务失败: ' . $e->getMessage();
            $this->errorLog($msg, ['task_id' => $taskId]);
            throw new \Exception($msg);
        }
    }

    /**
     * 关闭任务
     *
     * @param int $taskId
     * @return void
     * @throws \Exception
     */
    public function closeAction(int $taskId)
    {
        try {
            $originData = DetectionTaskMaster::query()->find($taskId);
            if (empty($originData)) {
                throw new \Exception('记录不存在');
            }
        } catch (\Exception $e) {
            $msg = '关闭任务-获取任务详情失败: ' . $e->getMessage();
            $this->errorLog($msg, ['task_id' => $taskId]);
            throw new \Exception($msg);
        }

        # 1. 调用火山内容洞察接口创建实时任务
        $insightHelper = new InsightAPI();

        try {
            $updateData = [
                'rule' => $originData['external_rule'],
                'enable_status' => 0, // 1-开启 0-关闭
                'task_id' => $originData->external_task_id,
                'sync_mode' => false
            ];
            $updateTaskResult = $insightHelper->bizSubUpdateTask($updateData);
            // todo mock 数据
            /*$updateTaskResult = [
                'status' => 0,
                'message' => 'succeed',
                'data' => [
                    'task_id' => 9999,
                    'enable_status' => 0
                ]
            ];*/
        } catch (\Exception $e) {
            $msg = '关闭任务-更新实时任务失败: ' . $e->getMessage();
            $this->errorLog($msg, ['data' => $updateData, 'update_task_result' => $updateTaskResult ?? []]);
            throw new \Exception($msg);
        }
        // 2. 更新数据库中的 external_enable_stats 状态
        try {
            $update = [
                // 火山实时任务状态
                'external_enable_status' => $updateTaskResult['data']['enable_status'],
                // 内部系统任务状态
                // 'status' => 2
            ];
            $originData->update($update);
        } catch (\Exception $e) {
            $msg = '关闭任务-更新实时任务失败: ' . $e->getMessage();
            $this->errorLog($msg, ['update' => $update ?? []]);
            throw new \Exception($msg);
        }
    }

    /**
     * 开启任务
     *
     * @param int $taskId
     * @return void
     * @throws \Exception
     */
    public function openAction(int $taskId)
    {
        try {
            $originData = DetectionTaskMaster::query()->find($taskId);
            if (empty($originData)) {
                throw new \Exception('记录不存在');
            }
        } catch (\Exception $e) {
            $msg = '开启任务-获取任务详情失败: ' . $e->getMessage();
            $this->errorLog($msg, ['task_id' => $taskId]);
            throw new \Exception($msg);
        }

        # 1. 调用火山内容洞察接口更新实时任务
        $insightHelper = new InsightAPI();

        try {
            $updateData = [
                'rule' => $originData['external_rule'],
                'enable_status' => 1, // 1-开启 0-关闭
                'task_id' => $originData->external_task_id,
                'sync_mode' => false
            ];
            $updateTaskResult = $insightHelper->bizSubUpdateTask($updateData);
            // todo mock 数据
            /*$updateTaskResult = [
                'status' => 0,
                'message' => 'succeed',
                'data' => [
                    'task_id' => 9999,
                    'enable_status' => 1
                ]
            ];*/
        } catch (\Exception $e) {
            $msg = '开启任务-更新实时任务失败: ' . $e->getMessage();
            $this->errorLog($msg, ['data' => $updateData, 'update_task_result' => $updateTaskResult ?? []]);
            throw new \Exception($msg);
        }
        // 2. 更新数据库中的 external_enable_stats 状态
        try {
            $update = [
                // 火山实时任务状态
                'external_enable_status' => $updateTaskResult['data']['enable_status'],
                // 内部系统任务状态
                // 'status' => 1
            ];
            $originData->update($update);
        } catch (\Exception $e) {
            $msg = '开启任务-更新实时任务失败: ' . $e->getMessage();
            $this->errorLog($msg, ['update' => $update ?? []]);
            throw new \Exception($msg);
        }
    }

    /**
     * 解析关键词标签
     *
     * @param string $textPlain
     * @return array[]
     * @throws \Exception
     */
    protected function parseKeyword(string $textPlain)
    {
        $keywordParser = new KeywordRuleParser();
        try {
            return $keywordParser->parse($textPlain);
        } catch (\InvalidArgumentException $e) {
            $msg = '关键词配置错误: ' . $e->getMessage();
            $this->errorLog($msg, ['text_plain' => $textPlain]);
            throw new \Exception($msg);
        } catch (\Exception $e) {
            $msg = '解析关键词错误: ' . $e->getMessage();
            $this->errorLog($msg, ['text_plain' => $textPlain]);
            throw new \Exception($msg);
        }
    }

    /**
     * 解析地域标签
     *
     * @param array $basedLocationPlain
     * @return array
     * @throws \Exception
     */
    protected function parsebasedLocation(array $basedLocationPlain)
    {
        $basedLocationParser = new BasedLocationParser();
        try {
            return $basedLocationParser->parseMultiple($basedLocationPlain);
        } catch (\Exception $e) {
            $msg = '解析地域错误: ' . $e->getMessage();
            $this->errorLog($msg, ['based_location_plain' => $basedLocationPlain]);
            throw new \Exception($msg);
        }
    }

    /**
     * 解析行业标签
     *
     * @param array $tagPlain
     * @return array
     * @throws \Exception
     */
    protected function parseTags(array $tagPlain)
    {
        $tagsParser = new TagsParser();
        try {
            return $tagsParser->parse($tagPlain);
        } catch (\Exception $e) {
            $msg = '解析行业标签错误: ' . $e->getMessage();
            $this->errorLog($msg, ['tag_plain' => $tagPlain]);
            throw new \Exception($msg);
        }
    }

    /**
     * 根据外部任务ID获取洞察数据列表
     *
     * @param array $params
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     * @throws ApiException
     */
    public function getInsightDataByExternalTaskId(array $params)
    {
        $externalTaskId = (int)($params['external_task_id'] ?? -1);
        $pageNum = (int)($params['pageNum'] ?? 1);
        $pageSize = (int)($params['pageSize'] ?? 10);

        if ($externalTaskId < 0) {
            throw new ApiException('外部任务ID不能为空');
        }

        // 验证任务是否存在
        $task = DetectionTaskMaster::query()
            ->where('external_task_id', $externalTaskId)
            ->first();

        if (empty($task)) {
            throw new ApiException('任务不存在');
        }

        // 查询匹配该外部任务ID的洞察数据
        return InsightPost::query()
            ->whereJsonContains('matched_task_ids', $externalTaskId)
            // source_type 发文来源类型
            ->when(isset($params['source_type']) && $params['source_type'] == '抖音', function ($q) use ($params) {
                $q->where('main_domain', 'douyin.com');
            })
            // publish_time_start 发布时间范围筛选
            ->when(isset($params['publish_time_start']) && $params['publish_time_start'] != '', function ($q) use ($params) {
                $q->where('publish_time', '>=', $params['publish_time_start']);
            })
            // publish_time_end 发布时间范围筛选
            ->when(isset($params['publish_time_end']) && $params['publish_time_end'] != '', function ($q) use ($params) {
                $q->where('publish_time', '<=', $params['publish_time_end']);
            })
            // list_order 发文时间排序
            ->when(isset($params['list_order']) && in_array($params['list_order'], ['asc', 'desc']), function ($q) use ($params) {
                $q->orderBy('publish_time', $params['list_order']);
            })
            ->when(isset($params['sentiment']) && in_array($params['sentiment'], [0, 1, 2]), function ($q) use ($params) {
                $q->where('sentiment', $params['sentiment']);
            })
            // 标题关键词搜索
            ->when(isset($params['title']) && $params['title'] != '', function ($q) use ($params) {
                $q->where('title', 'like', '%' . $params['title'] . '%');
            })
            // ->orderByDesc('publish_time')
            ->paginate($pageSize, ['*'], 'page', $pageNum);
    }

    /**
     * 记录错误日志
     *
     * @param string $msg
     * @param array $params
     * @return void
     */
    protected function errorLog(string $msg, array $params)
    {
        Log::channel('daily')->error($msg, $params);
    }
}
