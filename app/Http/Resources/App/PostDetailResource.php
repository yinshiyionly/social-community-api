<?php

namespace App\Http\Resources\App;

use App\Models\App\AppPostBase;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 帖子详情资源（v1）
 */
class PostDetailResource extends JsonResource
{
    private const DEFAULT_AVATAR = '';
    private const DEFAULT_NICKNAME = '用户';

    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request): array
    {
        $postType = (int)$this->post_type;
        $originCoverImage = $this->resolveCoverImage();
        $mediaFields = $this->resolveMediaFields($postType, $originCoverImage);
        $coverImage = $this->resolveFinalCoverImage($originCoverImage, $mediaFields);

        return [
            'id' => (string)$this->post_id,
            'postType' => $postType,
            'type' => 'dynamic',
            'title' => $this->title ?? '',
            'content' => $this->content ?? '',
            'coverImage' => $coverImage,
            'images' => $mediaFields['images'],
            'videoUrl' => $mediaFields['videoUrl'],
            'videoCover' => $mediaFields['videoCover'],
            'author' => $this->formatAuthor(),
            'likeCount' => $this->stat ? (int)$this->stat->like_count : 0,
            'favoriteCount' => $this->stat ? (int)$this->stat->collection_count : 0,
            'commentCount' => $this->stat ? (int)$this->stat->comment_count : 0,
            'createTime' => $this->created_at ? $this->created_at->toISOString() : null,
        ];
    }

    /**
     * @return string
     */
    private function resolveCoverImage(): string
    {
        $cover = $this->cover ?? [];
        return isset($cover['url']) ? (string)$cover['url'] : '';
    }

    /**
     * @param string $coverImage
     * @param array $mediaFields
     * @return string
     */
    private function resolveFinalCoverImage(string $coverImage, array $mediaFields): string
    {
        if ($coverImage !== '') {
            return $coverImage;
        }

        if (!empty($mediaFields['videoCover'])) {
            return (string)$mediaFields['videoCover'];
        }

        if (!empty($mediaFields['images'][0])) {
            return (string)$mediaFields['images'][0];
        }

        return '';
    }

    /**
     * @param int $postType
     * @param string $coverImage
     * @return array
     */
    private function resolveMediaFields(int $postType, string $coverImage): array
    {
        $mediaData = $this->media_data ?? [];

        if ($postType === AppPostBase::POST_TYPE_VIDEO) {
            $video = isset($mediaData[0]) && is_array($mediaData[0]) ? $mediaData[0] : [];
            $videoUrl = isset($video['url']) ? (string)$video['url'] : '';
            $videoCover = isset($video['cover']) ? (string)$video['cover'] : '';
            if ($videoCover === '') {
                $videoCover = $coverImage;
            }

            return [
                'images' => [],
                'videoUrl' => $videoUrl,
                'videoCover' => $videoCover,
            ];
        }

        if ($postType === AppPostBase::POST_TYPE_IMAGE_TEXT) {
            return [
                'images' => $this->extractImageUrls($mediaData),
                'videoUrl' => '',
                'videoCover' => '',
            ];
        }

        // 文章类型（以及兜底类型）
        return [
            'images' => [],
            'videoUrl' => '',
            'videoCover' => '',
        ];
    }

    /**
     * @param array $mediaData
     * @return array
     */
    private function extractImageUrls(array $mediaData): array
    {
        $images = [];
        foreach ($mediaData as $media) {
            if (isset($media['url']) && $media['url'] !== '') {
                $images[] = (string)$media['url'];
            }
        }

        return $images;
    }

    /**
     * @return array
     */
    private function formatAuthor(): array
    {
        $member = $this->member;
        $memberId = $member ? (int)$member->member_id : (int)$this->member_id;

        return [
            'id' => $memberId,
            'memberId' => $memberId,
            'nickname' => $member ? ($member->nickname ?? self::DEFAULT_NICKNAME) : self::DEFAULT_NICKNAME,
            'avatar' => $member ? ($member->avatar ?? self::DEFAULT_AVATAR) : self::DEFAULT_AVATAR,
            'badge' => '',
        ];
    }
}
