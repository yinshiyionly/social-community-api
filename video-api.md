# 视频发布页面接口文档

## 接口列表

### 1. 获取热门话题列表

**接口说明**  
获取当前热门话题列表，用于用户发布视频时选择话题

**请求方式**  
`GET`

**请求地址**  
`/api/post/topics/hot`

**请求参数**  
无

**请求示例**
```http
GET /api/post/topics/hot HTTP/1.1
Host: your-domain.com
Authorization: Bearer {token}
```

**响应参数**

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| list | Array | 是 | 话题列表 |
| list[].id | Number | 是 | 话题 ID |
| list[].rank | Number | 是 | 排名（1-N） |
| list[].name | String | 是 | 话题名称（包含 # 号，如：#再见 2025 你好 2026） |
| list[].views | String | 是 | 浏览量（如：61.2万次浏览） |
| total | Number | 否 | 总数（可选） |

**响应示例**
```json
{
  "code": 200,
  "message": "success",
  "data": {
    "list": [
      {
        "id": 1,
        "rank": 1,
        "name": "#再见 2025 你好 2026",
        "views": "61.2万次浏览"
      },
      {
        "id": 2,
        "rank": 2,
        "name": "#小寒风雪至",
        "views": "12万次浏览"
      }
    ],
    "total": 2
  }
}
```

---

### 2. 发布视频

**接口说明**  
发布视频内容，支持视频上传和话题关联

**请求方式**  
`POST`

**请求地址**  
`/api/post/video`

**请求头**
```
Content-Type: application/json
Authorization: Bearer {token}
```

**请求参数**

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| content | String | 是 | 视频文本内容（最多 500 字） |
| videoUrl | String | 是 | 视频 URL（通过上传接口获取） |
| topics | Array | 否 | 关联的话题列表（可选） |
| topics[].id | Number | 是 | 话题 ID |
| topics[].name | String | 是 | 话题名称 |

**请求示例**
```json
{
  "content": "分享一段精彩的视频",
  "videoUrl": "https://example.com/videos/abc123.mp4",
  "topics": [
    {
      "id": 1,
      "name": "#再见 2025 你好 2026"
    }
  ]
}
```

**响应参数**

| 参数名 | 类型 | 说明 |
|--------|------|------|
| id | Number | 视频 ID |
| createTime | String | 创建时间 |

**响应示例**
```json
{
  "code": 200,
  "message": "发布成功",
  "data": {
    "id": 12345,
    "createTime": "2026-01-29 10:30:00"
  }
}
```

---

### 3. 上传视频

**接口说明**  
上传视频文件，返回视频 URL 和封面 URL

**请求方式**  
`POST`

**请求地址**  
`/api/app/v1/common/uploadVideo`

**请求头**
```
Content-Type: multipart/form-data
Authorization: Bearer {token}
```

**请求参数**

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| file | File | 是 | 视频文件（支持 MP4、MOV 等格式） |

**文件限制**
- 支持格式：MP4、MOV、AVI 等
- 最大大小：500MB
- 建议时长：无限制

**响应参数**

| 参数名 | 类型 | 说明 |
|--------|------|------|
| url | String | 视频访问地址 |

**响应示例**
```json
{
  "code": 200,
  "message": "上传成功",
  "data": {
    "url": "https://example.com/videos/2026/01/29/abc123.mp4"
  }
}
```

---

## 业务流程说明

### 发布视频流程

1. **用户打开发布页面**
   - 页面初始化，准备接收用户输入

2. **用户输入内容**
   - 输入文本内容（最多 500 字）
   - 点击上传按钮选择视频
   - 可选择关联话题

3. **上传视频**
   - 用户点击上传区域选择视频
   - 使用 `WdUpload` 组件自动上传
   - 调用 `POST /api/app/v1/common/uploadVideo` 上传视频
   - 显示上传进度
   - 上传成功后获取 videoUrl
   - 显示视频预览

4. **选择话题（可选）**
   - 用户点击"更多话题"按钮
   - 调用 `GET /api/post/topics/hot` 获取热门话题列表
   - 用户从列表中选择话题
   - 已选话题显示为标签，可点击 × 移除

6. **发布视频**
   - 用户点击"发布"按钮
   - 验证：内容和视频至少有一个
   - 检查视频是否上传完成
   - 组装参数：content、videoUrl、topics（可选）
   - 调用 `POST /api/post/video` 发布
   - 发布成功后显示提示，可选择返回上一页

---

## 前端实现说明

### 技术栈
- uni-app + Vue3 + TypeScript
- wot-design-uni 组件库
- Composition API

### 关键文件
- 页面：`src/pages/post/video.vue`
- 接口：`src/service/post.ts`
- 类型：`src/types/post.ts`
- Hooks：`src/hooks/useTopicSelect.ts`

### 状态管理
- `content`: 视频文本内容
- `fileList`: 上传的视频文件列表
- `selectedTopics`: 已选择的话题列表
- `topicList`: 热门话题列表
- `loadingTopics`: 话题加载状态

### 核心功能
1. **视频上传**：使用 `WdUpload` 组件，accept="video"
2. **视频预览**：组件自动处理预览
3. **话题选择**：复用 `useTopicSelect` Hook
4. **发布视频**：调用 `publishVideo` 接口

---

## 与动态发布的差异

| 功能 | 动态发布 | 视频发布 |
|------|----------|----------|
| 媒体类型 | 图片（最多 9 张） | 视频（1 个） |
| 上传组件 | `<WdUpload>` | `<WdUpload accept="video">` |
| 上传接口 | `/api/app/v1/common/uploadImage` | `/api/app/v1/common/uploadVideo` |
| 发布接口 | `/api/post/dynamic` | `/api/post/video` |
| 文件大小限制 | 5MB/张 | 500MB |
| 数量限制 | 最多 9 张 | 1 个 |

---

## 联调建议

1. **先测试获取话题列表接口**
   - 确保返回的数据格式正确
   - 验证排名和浏览量的格式

2. **测试视频上传接口**
   - 测试不同格式的视频（MP4、MOV 等）
   - 测试不同大小的视频
   - 测试上传失败的情况
   - 验证返回的 videoUrl 和 coverUrl 是否可访问

3. **测试发布视频接口**
   - 测试纯文本发布
   - 测试视频发布
   - 测试带话题的发布
   - 测试各种异常情况（内容为空、视频未上传等）

4. **测试异常场景**
   - token 过期
   - 网络错误
   - 参数错误
   - 服务器错误
   - 文件过大
   - 不支持的格式

5. **性能测试**
   - 大文件上传速度
   - 上传进度显示
   - 视频预览加载速度

---

## 更新记录

| 日期 | 版本 | 说明 |
|------|------|------|
| 2026-01-29 | v1.0 | 初始版本 |
