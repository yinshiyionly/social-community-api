# 动态发布页面接口文档

## 接口列表

### 1. 获取热门话题列表

**接口说明**  
获取当前热门话题列表，用于用户发布动态时选择话题

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
      },
      {
        "id": 3,
        "rank": 3,
        "name": "#冬至小团圆",
        "views": "21万次浏览"
      }
    ],
    "total": 3
  }
}
```

---

### 2. 发布动态

**接口说明**  
发布图文动态，支持多图上传和话题关联

**请求方式**  
`POST`

**请求地址**  
`/api/post/dynamic`

**请求头**
```
Content-Type: application/json
Authorization: Bearer {token}
```

**请求参数**

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| content | String | 是 | 动态文本内容（最多 500 字） |
| images | Array | 是 | 图片 URL 数组（最多 9 张，可为空数组） |
| topics | Array | 否 | 关联的话题列表（可选） |
| topics[].id | Number | 是 | 话题 ID |
| topics[].name | String | 是 | 话题名称 |

**请求示例**
```json
{
  "content": "今天天气真好，分享一下美景",
  "images": [
    "https://example.com/image1.jpg",
    "https://example.com/image2.jpg"
  ],
  "topics": [
    {
      "id": 1,
      "name": "#再见 2025 你好 2026"
    },
    {
      "id": 4,
      "name": "#美无处不在"
    }
  ]
}
```

**响应参数**

| 参数名 | 类型 | 说明 |
|--------|------|------|
| id | Number | 动态 ID |
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

### 3. 上传图片

**接口说明**  
上传单张图片，用于动态发布时的图片上传

**请求方式**  
`POST`

**请求地址**  
`/api/app/v1/common/uploadImage`

**请求头**
```
Content-Type: multipart/form-data
Authorization: Bearer {token}
```

**请求参数**

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| file | File | 是 | 图片文件（支持 jpg、png、gif 等格式） |

**响应参数**

| 参数名 | 类型 | 说明 |
|--------|------|------|
| url | String | 图片访问地址 |

**响应示例**
```json
{
  "code": 200,
  "message": "上传成功",
  "data": {
    "url": "https://example.com/uploads/2026/01/29/abc123.jpg"
  }
}
```

---

## 业务流程说明

### 发布动态流程

1. **用户打开发布页面**
   - 页面初始化，准备接收用户输入

2. **用户输入内容**
   - 输入文本内容（最多 500 字）
   - 选择上传图片（最多 9 张）
   - 可选择关联话题

3. **选择话题（可选）**
   - 用户点击"更多话题"按钮
   - 调用 `GET /api/post/topics/hot` 获取热门话题列表
   - 用户从列表中选择话题
   - 已选话题显示为标签，可点击 × 移除

4. **上传图片**
   - 用户选择图片后，自动调用 `POST /api/app/v1/common/uploadImage` 上传
   - 每张图片单独上传，获取图片 URL
   - 上传失败的图片会提示用户

5. **发布动态**
   - 用户点击"发布"按钮
   - 验证：内容和图片至少有一个
   - 组装参数：content、images（URL 数组）、topics（可选）
   - 调用 `POST /api/post/dynamic` 发布
   - 发布成功后显示提示，可选择返回上一页

---

## 错误码说明

| 错误码 | 说明 | 处理方式 |
|--------|------|----------|
| 200 | 成功 | 正常处理 |
| 400 | 请求参数错误 | 检查参数格式和必填项 |
| 401 | 未登录或 token 过期 | 跳转登录页 |
| 403 | 无权限 | 提示用户无权限 |
| 500 | 服务器错误 | 提示用户稍后重试 |

---

## 注意事项

1. **图片上传**
   - 图片大小建议不超过 5MB
   - 支持格式：jpg、jpeg、png、gif
   - 最多上传 9 张图片
   - 图片上传失败时，前端会提示用户

2. **话题选择**
   - 话题名称包含 # 号
   - 可以不选择话题
   - 可以选择多个话题
   - 话题列表按排名排序，前三名显示奖牌图标

3. **内容验证**
   - 文本内容最多 500 字
   - 内容和图片至少有一个
   - 空白内容会被拦截

4. **认证要求**
   - 所有接口都需要在请求头中携带 token
   - token 格式：`Authorization: Bearer {token}`

---

## 前端实现说明

### 技术栈
- uni-app + Vue3 + TypeScript
- wot-design-uni 组件库
- Composition API

### 关键文件
- 页面：`src/pages/post/dynamic.vue`
- 接口：`src/service/post.ts`
- 类型：`src/types/post.ts`

### 状态管理
- `content`: 动态文本内容
- `fileList`: 上传的图片列表
- `selectedTopics`: 已选择的话题列表
- `topicList`: 热门话题列表
- `loadingTopics`: 话题加载状态

---

## 联调建议

1. **先测试获取话题列表接口**
   - 确保返回的数据格式正确
   - 验证排名和浏览量的格式

2. **测试图片上传接口**
   - 测试单张图片上传
   - 测试多张图片上传
   - 测试上传失败的情况

3. **测试发布动态接口**
   - 测试纯文本发布
   - 测试图文发布
   - 测试带话题的发布
   - 测试各种异常情况（内容为空、图片上传失败等）

4. **测试异常场景**
   - token 过期
   - 网络错误
   - 参数错误
   - 服务器错误

---

## 更新记录

| 日期 | 版本 | 说明 |
|------|------|------|
| 2026-01-29 | v1.0 | 初始版本 |
