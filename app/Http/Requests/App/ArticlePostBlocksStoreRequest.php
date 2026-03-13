<?php

namespace App\Http\Requests\App;

use App\Http\Requests\App\Traits\PostStoreRequestTrait;
use App\Models\App\AppPostBase;
use App\Models\App\AppTopicBase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * App 文章发帖请求（blocks 协议）。
 *
 * 职责：
 * 1. 校验 blocks 的结构与媒体类型约束；
 * 2. 校验 topics 是否存在且可用；
 * 3. 将请求标准化为 PostService::createPost 可直接落库的数据结构。
 */
class ArticlePostBlocksStoreRequest extends FormRequest
{
    use PostStoreRequestTrait;

    /**
     * 文章帖子最多选择 3 个话题。
     */
    private const MAX_TOPICS = 3;

    /**
     * 允许的 block 类型。
     */
    private const BLOCK_TYPES = ['text', 'image', 'video'];

    /**
     * 是否允许发起请求。
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 获取校验规则。
     *
     * 说明：
     * - blocks 的「按 type 强约束字段」在 after 校验中处理，避免规则表达过于分散；
     * - topics 的存在性校验同样在 after 中统一处理。
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:50',
            'coverImage' => 'sometimes|nullable|string|max:500',
            'blocks' => 'required|array|min:1',
            'blocks.*' => 'required|array',
            'blocks.*.type' => 'required|string|in:text,image,video',
            'blocks.*.text' => 'sometimes|nullable|string',
            'blocks.*.url' => 'sometimes|nullable|string|max:500',
            'blocks.*.poster' => 'sometimes|nullable|string|max:500',
            'topics' => 'sometimes|nullable|array|max:' . self::MAX_TOPICS,
            'topics.*.id' => 'required|integer|min:1',
            'topics.*.name' => 'required|string|max:100',
            'article_cover_style' => 'sometimes|integer|in:1,2,3',
            'visible' => 'sometimes|integer|in:0,1',
        ];
    }

    /**
     * 追加业务校验。
     *
     * @param Validator $validator
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $this->validateBlocks($validator);
            $this->validateTopicsExist($validator);
        });
    }

    /**
     * 校验 blocks 的分支规则。
     *
     * 规则：
     * 1. text 必须有 text；
     * 2. image 必须有 url 且必须为图片地址；
     * 3. video 必须有 url 且必须为视频地址。
     *
     * @param Validator $validator
     * @return void
     */
    protected function validateBlocks(Validator $validator): void
    {
        $blocks = $this->input('blocks', []);
        if (!is_array($blocks) || empty($blocks)) {
            return;
        }

        foreach ($blocks as $index => $block) {
            if (!is_array($block)) {
                continue;
            }

            $type = (string)($block['type'] ?? '');
            if (!in_array($type, self::BLOCK_TYPES, true)) {
                continue;
            }

            if ($type === 'text') {
                $text = trim((string)($block['text'] ?? ''));
                if ($text === '') {
                    $validator->errors()->add("blocks.$index.text", 'text 类型 block 必须包含 text');
                }
                continue;
            }

            $url = trim((string)($block['url'] ?? ''));
            if ($url === '') {
                $validator->errors()->add("blocks.$index.url", $type . ' 类型 block 必须包含 url');
                continue;
            }

            // 通过文件后缀约束 image/video 的媒体类型，避免错误文件进入正文块。
            if ($type === 'image' && !$this->isImageUrl($url)) {
                $validator->errors()->add("blocks.$index.url", 'image 类型 block 的 url 必须是图片地址');
            }
            if ($type === 'video' && !$this->isVideoUrl($url)) {
                $validator->errors()->add("blocks.$index.url", 'video 类型 block 的 url 必须是视频地址');
            }
        }
    }

    /**
     * 校验话题是否存在且正常。
     *
     * @param Validator $validator
     * @return void
     */
    protected function validateTopicsExist(Validator $validator): void
    {
        $topics = $this->input('topics', []);
        if (!is_array($topics) || empty($topics)) {
            return;
        }

        $topicIds = array_values(array_unique(array_filter(array_column($topics, 'id'))));
        if (empty($topicIds)) {
            return;
        }

        $existCount = AppTopicBase::query()
            ->whereIn('topic_id', $topicIds)
            ->normal()
            ->count();

        if ($existCount !== count($topicIds)) {
            $validator->errors()->add('topics', '选择的话题不存在或已下线');
        }
    }

    /**
     * 获取校验错误文案。
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return array_merge($this->commonMessages(), [
            'title.required' => '请输入文章标题',
            'title.max' => '标题最多50字',
            'coverImage.max' => '封面URL过长',
            'blocks.required' => '请填写文章内容',
            'blocks.array' => 'blocks 格式不正确',
            'blocks.min' => '请至少添加一个内容块',
            'blocks.*.array' => 'blocks 子项格式不正确',
            'blocks.*.type.required' => 'block 类型不能为空',
            'blocks.*.type.in' => 'block 类型仅支持 text、image、video',
            'blocks.*.url.max' => '媒体URL过长',
            'blocks.*.poster.max' => '视频封面URL过长',
            'topics.max' => '最多选择' . self::MAX_TOPICS . '个话题',
            'topics.*.id.required' => '话题ID不能为空',
            'topics.*.id.integer' => '话题ID格式错误',
            'topics.*.name.required' => '话题名称不能为空',
            'topics.*.name.max' => '话题名称过长',
            'article_cover_style.in' => '文章封面样式不正确',
        ]);
    }

    /**
     * 获取标准化后的发帖数据。
     *
     * 关键规则：
     * 1. media_data 直接落 blocks，保持顺序不变；
     * 2. content 仅由 text blocks 拼接，供摘要/消息等非详情场景使用；
     * 3. cover 按 coverImage -> 首图 -> 首个视频 poster 的顺序兜底。
     *
     * @return array<string, mixed>
     */
    public function validatedWithDefaults(): array
    {
        $data = $this->validated();
        $blocks = $this->normalizeBlocks($data['blocks'] ?? []);

        return [
            'post_type' => AppPostBase::POST_TYPE_ARTICLE,
            'title' => (string)($data['title'] ?? ''),
            'content' => $this->buildContentFromBlocks($blocks),
            'media_data' => $blocks,
            'cover' => $this->resolveCover((string)($data['coverImage'] ?? ''), $blocks),
            'image_show_style' => AppPostBase::IMAGE_SHOW_STYLE_LARGE,
            'article_cover_style' => (int)($data['article_cover_style'] ?? AppPostBase::ARTICLE_COVER_STYLE_SINGLE),
            'visible' => (int)($data['visible'] ?? AppPostBase::VISIBLE_PUBLIC),
            'topics' => $this->input('topics', []),
        ];
    }

    /**
     * 归一化 blocks 数据。
     *
     * @param array<int, mixed> $blocks
     * @return array<int, array<string, string>>
     */
    protected function normalizeBlocks(array $blocks): array
    {
        $result = [];

        foreach ($blocks as $block) {
            if (!is_array($block)) {
                continue;
            }

            $type = (string)($block['type'] ?? '');
            if ($type === 'text') {
                $result[] = [
                    'type' => 'text',
                    'text' => (string)($block['text'] ?? ''),
                ];
                continue;
            }

            if ($type === 'image') {
                $result[] = [
                    'type' => 'image',
                    'url' => (string)($block['url'] ?? ''),
                ];
                continue;
            }

            if ($type === 'video') {
                $videoBlock = [
                    'type' => 'video',
                    'url' => (string)($block['url'] ?? ''),
                ];

                $poster = trim((string)($block['poster'] ?? ''));
                if ($poster !== '') {
                    $videoBlock['poster'] = $poster;
                }

                $result[] = $videoBlock;
            }
        }

        return $result;
    }

    /**
     * 从 blocks 生成 content 文本。
     *
     * @param array<int, array<string, string>> $blocks
     * @return string
     */
    protected function buildContentFromBlocks(array $blocks): string
    {
        $texts = [];
        foreach ($blocks as $block) {
            if (($block['type'] ?? '') !== 'text') {
                continue;
            }

            $text = trim((string)($block['text'] ?? ''));
            if ($text !== '') {
                $texts[] = $text;
            }
        }

        return implode("\n", $texts);
    }

    /**
     * 解析封面。
     *
     * @param string $coverImage
     * @param array<int, array<string, string>> $blocks
     * @return array<string, string>
     */
    protected function resolveCover(string $coverImage, array $blocks): array
    {
        $coverImage = trim($coverImage);
        if ($coverImage !== '') {
            return ['url' => $coverImage];
        }

        foreach ($blocks as $block) {
            if (($block['type'] ?? '') === 'image' && !empty($block['url'])) {
                return ['url' => (string)$block['url']];
            }
        }

        foreach ($blocks as $block) {
            if (($block['type'] ?? '') === 'video' && !empty($block['poster'])) {
                return ['url' => (string)$block['poster']];
            }
        }

        return [];
    }
}
