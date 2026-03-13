# 文章详情接口对接规范（按 blocks 渲染）

本规范以**实际发布文章功能**为依据：文章内容是由 **blocks** 组成（文字/图片/视频）。
为了保证文章详情页**按发布顺序**稳定展示，后端必须返回 **blocks 数组**。

---

## 1. 返回结构（必须字段）

```json
{
  "code": 200,
  "msg": "success",
  "data": {
    "id": 123456,
    "postType": 3,
    "title": "文章标题",
    "blocks": [
      { "type": "text", "text": "第一段文字" },
      { "type": "image", "url": "https://...jpg" },
      { "type": "text", "text": "第二段文字" },
      { "type": "video", "url": "https://...mp4", "poster": "https://...jpg" }
    ],
    "topics": [
      { "id": 1, "name": "话题A" }
    ],
    "coverImage": "https://...jpg",
    "createTime": "2026-03-13T15:47:39+08:00",
    "author": {
      "memberId": 10001,
      "nickname": "作者昵称",
      "avatar": "https://...jpg"
    },
    "likeCount": 0,
    "commentCount": 0,
    "favoriteCount": 0,
    "isLiked": false,
    "isFavorited": false
  }
}
```

### 字段说明（必须）
- `id`：文章 ID（数字或字符串）
- `postType`：文章类型，固定 `3`
- `title`：文章标题
- `blocks`：**文章内容块数组（顺序即展示顺序）**
- `topics`：话题数组 `{ id, name }`（用于详情页底部蓝色标签）
- `coverImage`：封面图（默认首图；无图可用视频首帧）
- `createTime`：发布时间（字符串）
- `author`：作者信息（`memberId/nickname/avatar`）
- `likeCount/commentCount/favoriteCount/isLiked/isFavorited`：互动字段

---

## 2. blocks 结构规范（必需）

### 2.1 文字块
```json
{ "type": "text", "text": "正文文字" }
```
- `text`：纯文本，可包含换行

### 2.2 图片块
```json
{ "type": "image", "url": "https://...jpg" }
```
- `url`：图片完整 URL

### 2.3 视频块
```json
{ "type": "video", "url": "https://...mp4", "poster": "https://...jpg" }
```
- `url`：视频完整 URL
- `poster`：视频封面（建议必传，避免黑屏）

> 说明：`blocks` 必须保持发布时顺序，前端按数组顺序渲染。

---

## 3. content/images/videoUrls 字段处理

**按 blocks 渲染时，前端不再依赖以下字段：**
- `content`
- `images`
- `videoUrls`

如果后端仍想返回这些字段，仅作为检索/分享等用途即可，**详情展示以 blocks 为准**。

---

## 4. 封面图规则

建议 `coverImage` 规则：
1) 优先取文章第一张图片
2) 无图片时，用视频首帧（上传视频时后端生成）

---

## 5. 示例场景

### 场景 A：纯文字
```json
{
  "title": "标题",
  "blocks": [
    { "type": "text", "text": "只有文字" }
  ],
  "topics": [
    { "id": 1, "name": "话题A" }
  ]
}
```

### 场景 B：文字 + 图片
```json
{
  "title": "标题",
  "blocks": [
    { "type": "text", "text": "文字" },
    { "type": "image", "url": "https://...jpg" }
  ]
}
```

### 场景 C：文字 + 视频
```json
{
  "title": "标题",
  "blocks": [
    { "type": "text", "text": "文字" },
    { "type": "video", "url": "https://...mp4", "poster": "https://...jpg" }
  ]
}
```

### 场景 D：文字 + 图片 + 文字 + 视频
```json
{
  "title": "标题",
  "blocks": [
    { "type": "text", "text": "第一段文字" },
    { "type": "image", "url": "https://...jpg" },
    { "type": "text", "text": "第二段文字" },
    { "type": "video", "url": "https://...mp4", "poster": "https://...jpg" }
  ]
}
```

---

## 6. 强制约束总结（必须遵守）

- `blocks` 必须返回且顺序正确
- `blocks.type` 必须是 `text/image/video`
- `text` 块必须有 `text`
- `image` 块必须有 `url`
- `video` 块必须有 `url`，建议带 `poster`

只要后端按以上规范返回，文章详情页即可稳定展示文字/图片/视频并保持顺序。
