# 文章发布接口对接规范（按 blocks 发送）

本规范以**实际发布文章功能**为依据：文章内容由 **blocks** 组成（文字/图片/视频）。
前端发布时只发送 blocks，并由后端负责持久化与后续详情返回。

---

## 1. 发布接口入参（必须字段）

```json
{
  "title": "文章标题",
  "coverImage": "https://...jpg",
  "topics": [
    { "id": 1, "name": "话题A" }
  ],
  "blocks": [
    { "type": "text", "text": "第一段文字" },
    { "type": "image", "url": "https://...jpg" },
    { "type": "text", "text": "第二段文字" },
    { "type": "video", "url": "https://...mp4", "poster": "https://...jpg" }
  ]
}
```

### 字段说明
- `title`：文章标题
- `coverImage`：封面图（默认首图；无图用视频首帧）
- `topics`：话题数组 `{ id, name }`（可选）
- `blocks`：文章内容块数组（顺序即展示顺序）

---

## 2. blocks 结构规范（必需）

### 2.1 文字块
```json
{ "type": "text", "text": "正文文字" }
```

### 2.2 图片块
```json
{ "type": "image", "url": "https://...jpg" }
```

### 2.3 视频块
```json
{ "type": "video", "url": "https://...mp4", "poster": "https://...jpg" }
```
- `poster`：视频封面（建议必传，避免详情页黑屏）

---

## 3. 强制约束总结

- `blocks` 必须存在且顺序正确
- `blocks.type` 必须是 `text/image/video`
- `text` 块必须有 `text`
- `image` 块必须有 `url`
- `video` 块必须有 `url`，建议带 `poster`

---

## 4. 与详情接口的配合

后端保存 `blocks` 后，文章详情接口应直接返回 `blocks`，以保证前端详情页按顺序渲染。

详情接口规范见：`docs/article_detail_api.md`
