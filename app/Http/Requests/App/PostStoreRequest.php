<?php

namespace App\Http\Requests\App;

use App\Models\App\AppPostBase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * 发表帖子请求验证
 */
class PostStoreRequest extends FormRequest
{
    /**
     * 允许的图片后缀
     */
    const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];

    /**
     * 允许的视频后缀
     */
    const VIDEO_EXTENSIONS = ['mp4', 'mov', 'avi', 'wmv', 'flv', 'mkv', 'webm'];

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $postType = $this->input('post_type');

        $rules = [
            'post_type' => 'required|integer|in:1,2,3',
            'title' => 'sometimes|nullable|string|max:50',
            'content' => 'sometimes|nullable|string|max:500',
            'media_data' => 'sometimes|nullable|array',
            'media_data.*' => 'required|string|max:500',
            'cover' => 'sometimes|nullable|string|max:500',
            'image_style' => 'sometimes|integer|in:1,2',
            'location_name' => 'sometimes|nullable|string|max:100',
            'location_geo' => 'sometimes|nullable|array',
            'location_geo.lat' => 'sometimes|nullable|numeric',
            'location_geo.lng' => 'sometimes|nullable|numeric',
            'visible' => 'sometimes|integer|in:0,1',
        ];

        // 图文动态：content 和 media_data 至少有一个
        if ($postType == AppPostBase::POST_TYPE_IMAGE_TEXT) {
            $rules['media_data'] = 'sometimes|nullable|array|max:9';
        } elseif ($postType == AppPostBase::POST_TYPE_VIDEO) {
            // 视频动态：必须有1个视频
            $rules['media_data'] = 'required|array|size:1';
        } elseif ($postType == AppPostBase::POST_TYPE_ARTICLE) {
            // 文章动态：content 必填，长度限制 10000 个字符
            $rules['content'] = 'required|string|max:10000';
            $rules['media_data'] = 'sometimes|nullable|array|max:9';
        } else {
            $rules['media_data'] = 'sometimes|nullable|array|max:9';
        }

        return $rules;
    }

    /**
     * 配置验证器实例
     *
     * @param Validator $validator
     * @return void
     */
    public function withValidator(Validator $validator)
    {
        $validator->after(function (Validator $validator) {
            $this->validateImageTextRequired($validator);
            $this->validateMediaType($validator);
        });
    }

    /**
     * 验证图文动态：content 和 media_data 至少有一个
     *
     * @param Validator $validator
     * @return void
     */
    protected function validateImageTextRequired(Validator $validator)
    {
        $postType = $this->input('post_type');

        if ($postType != AppPostBase::POST_TYPE_IMAGE_TEXT) {
            return;
        }

        $content = trim($this->input('content', ''));
        $mediaData = $this->input('media_data', []);

        if (empty($content) && empty($mediaData)) {
            $validator->errors()->add('content', '请输入文字或者上传图片，才可以发布哦');
        }
    }

    /**
     * 验证媒体文件类型（通过后缀）
     *
     * @param Validator $validator
     * @return void
     */
    protected function validateMediaType(Validator $validator)
    {
        $postType = $this->input('post_type');
        $mediaData = $this->input('media_data', []);

        if (empty($mediaData) || !is_array($mediaData)) {
            return;
        }

        // 图文动态：必须都是图片
        if ($postType == AppPostBase::POST_TYPE_IMAGE_TEXT) {
            foreach ($mediaData as $url) {
                if (!$this->isImageUrl($url)) {
                    $validator->errors()->add('media_data', '图文动态只能上传图片');
                    return;
                }
            }
        }

        // 视频动态：必须是视频
        if ($postType == AppPostBase::POST_TYPE_VIDEO) {
            foreach ($mediaData as $url) {
                if (!$this->isVideoUrl($url)) {
                    $validator->errors()->add('media_data', '视频动态只能上传视频');
                    return;
                }
            }
        }
    }

    /**
     * 判断 URL 是否为图片
     *
     * @param string $url
     * @return bool
     */
    protected function isImageUrl(string $url): bool
    {
        $ext = $this->getUrlExtension($url);
        return in_array($ext, self::IMAGE_EXTENSIONS);
    }

    /**
     * 判断 URL 是否为视频
     *
     * @param string $url
     * @return bool
     */
    protected function isVideoUrl(string $url): bool
    {
        $ext = $this->getUrlExtension($url);
        return in_array($ext, self::VIDEO_EXTENSIONS);
    }

    /**
     * 获取 URL 的文件后缀
     *
     * @param string $url
     * @return string
     */
    protected function getUrlExtension(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (!$path) {
            return '';
        }
        return strtolower(pathinfo($path, PATHINFO_EXTENSION));
    }

    public function messages()
    {
        return [
            'post_type.required' => '请选择动态类型',
            'post_type.in' => '动态类型不正确',
            'content.required' => '请输入内容',
            'content.max' => '内容最多:max字',
            'media_data.required' => '请上传图片或视频',
            'media_data.min' => '至少上传:min个媒体文件',
            'media_data.max' => '最多上传:max个媒体文件',
            'media_data.size' => '视频动态只能上传:size个视频',
            'media_data.*.string' => '媒体文件URL格式错误',
            'cover.string' => '封面图URL格式错误',
            'location_name.max' => '位置名称最多100字',
            'content_html.required' => '请输入文章内容',
            'content_html.max' => '文章内容过长',
        ];
    }

    /**
     * 获取验证后的数据并设置默认值
     *
     * @return array
     */
    public function validatedWithDefaults(): array
    {
        $data = $this->validated();

        // 设置默认值
        $data['title'] = $data['title'] ?? '';
        $data['content'] = $data['content'] ?? '';
        $data['image_style'] = $data['image_style'] ?? AppPostBase::IMAGE_STYLE_LARGE;
        $data['location_name'] = $data['location_name'] ?? '';
        $data['location_geo'] = $data['location_geo'] ?? [];
        $data['visible'] = $data['visible'] ?? AppPostBase::VISIBLE_PUBLIC;

        // media_data: 将 URL 字符串数组转为对象数组，其他字段由异步队列补充
        if (!empty($data['media_data'])) {
            $data['media_data'] = array_map(function ($url) {
                return ['url' => $url];
            }, $data['media_data']);
        } else {
            $data['media_data'] = [];
        }

        // post_type = 1 图文动态使用 media_data 的第一个图片作为封面
        if (!empty($data['media_data']) && $data['post_type'] == AppPostBase::POST_TYPE_IMAGE_TEXT) {
            $data['cover'] = $data['media_data'][0]['url'];
        }

        // cover: 将 URL 字符串转为对象，其他字段由异步队列补充
        if (!empty($data['cover'])) {
            $data['cover'] = ['url' => $data['cover']];
        } else {
            $data['cover'] = [];
        }

        return $data;
    }
}
