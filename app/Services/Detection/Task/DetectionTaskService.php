<?php

namespace App\Services\Detection\Task;

use App\Exceptions\ApiException;
use App\Helper\KeywordParser\BasedLocationParser;
use App\Helper\KeywordParser\KeywordRuleParser;
use App\Helper\KeywordParser\TagsParser;
use App\Helper\Volcengine\InsightAPI;
use App\Models\Detection\DetectionTaskMaster;
use Illuminate\Support\Facades\Log;

class DetectionTaskService
{
    public function getList(array $params)
    {
        $pageNum = (int)($params['pageNum'] ?? 1);
        $pageSize = (int)($params['pageSize'] ?? 10);

        return DetectionTaskMaster::query()
            ->when(isset($params['keyword']) && $params['keyword'] != '', function ($q) use ($params) {
                $q->where('text_plain', 'like', '%' . $params['keyword'] . '%');
            })
            ->orderByDesc('id')
            ->paginate($pageSize, ['*'], 'page', $pageNum);
    }

    public function getById(int $id)
    {
        $item = DetectionTaskMaster::find($id);

        if (empty($item)) {
            throw new ApiException('记录不存在');
        }

        return $item;
    }

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
            $this->errorLog($msg, ['data' => $data]);
            throw new \Exception($msg);
        }

        // todo 拆成异步
        # 2. 过滤文本关键词中的敏感词

        # 3. 调用火山内容洞察接口创建实时任务
        $insightHelper = new InsightAPI();

        $createTaskResult = $insightHelper->bizSubCreateTask([
            'rule' => $rule,
            'sync_mode' => false
        ]);






        dd(
            1231
        );


    }

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

    protected function parsebasedLocation(array $basedLocationPlain)
    {
        $basedLocationParser = new BasedLocationParser();
        try {
            return $basedLocationParser->parse($basedLocationPlain);
        } catch (\Exception $e) {
            $msg = '解析地域错误: ' . $e->getMessage();
            $this->errorLog($msg, ['based_location_plain' => $basedLocationPlain]);
            throw new \Exception($msg);
        }
    }

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

    protected function errorLog(string $msg, array $params)
    {
        Log::channel('daily')->error($msg, $params);
    }
}
