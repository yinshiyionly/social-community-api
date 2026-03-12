# Admin 模块帖子管理接口文档

## 1. 接口列表总览

| 功能 | 方法 | 路径 |
|---|---|---|
| 帖子模块常量选项 | GET | `/api/admin/post/constants` |
| 官方发帖账号下拉选项 | GET | `/api/admin/post/officialMemberOptionselect` |
| 后台发布图文帖子 | POST | `/api/admin/post/store/imageText` |
| 后台发布视频帖子 | POST | `/api/admin/post/store/video` |
| 后台发布文章帖子 | POST | `/api/admin/post/store/article` |
| 帖子分页列表 | GET | `/api/admin/post/list` |
| 帖子详情 | GET | `/api/admin/post/{postId}` |
| 帖子审核（通过/拒绝） | PUT | `/api/admin/post/audit` |

## 2. 通用说明

- 鉴权：所有接口都需要通过 `system.auth` 中间件鉴权（建议携带 `Authorization: Bearer {token}`）。
- 响应外层结构：
  - 分页接口：`code`、`msg`、`total`、`rows`
  - 非分页接口：`code`、`msg`、`data`
- 失败码：当前后台业务失败与参数失败统一返回 `1201`。

### 2.1 请求头建议

```http
Accept: application/json
Authorization: Bearer {token}
Content-Type: application/json
```

### 2.2 通用响应示例

#### 成功响应（分页）

```json
{
    "code": 200,
    "msg": "查询成功",
    "total": 2,
    "rows": []
}
```

#### 成功响应（非分页）

```json
{
    "code": 200,
    "msg": "操作成功",
    "data": []
}
```

#### 失败响应

```json
{
    "code": 1201,
    "msg": "操作失败",
    "data": []
}
```

### 2.3 枚举说明

- 以下枚举值可通过 `GET /api/admin/post/constants` 接口统一获取。

| 枚举 | 值 | 说明 |
|---|---|---|
| postType | `1` | 图文 |
| postType | `2` | 视频 |
| postType | `3` | 文章 |
| visible | `0` | 私密 |
| visible | `1` | 公开 |
| status | `0` | 待审核 |
| status | `1` | 已通过 |
| status | `2` | 已拒绝 |
| imageShowStyle | `1` | 大图 |
| imageShowStyle | `2` | 拼图 |
| articleCoverStyle | `1` | 单图 |
| articleCoverStyle | `2` | 双图 |
| articleCoverStyle | `3` | 三图 |

## 3. 详细接口说明

### 3.1 帖子模块常量选项

- 方法：`GET`
- 路径：`/api/admin/post/constants`
- 参数：无
- 说明：返回帖子管理页面最小必需枚举选项（帖子类型、可见性、审核状态）。

#### 响应示例 JSON（成功）

```json
{
    "code": 200,
    "msg": "查询成功",
    "data": {
        "postTypeOptions": [
            {
                "label": "图文",
                "value": 1
            },
            {
                "label": "视频",
                "value": 2
            },
            {
                "label": "文章",
                "value": 3
            }
        ],
        "visibleOptions": [
            {
                "label": "私密",
                "value": 0
            },
            {
                "label": "公开",
                "value": 1
            }
        ],
        "statusOptions": [
            {
                "label": "待审核",
                "value": 0
            },
            {
                "label": "已通过",
                "value": 1
            },
            {
                "label": "已拒绝",
                "value": 2
            }
        ]
    }
}
```

#### `data` 字段说明

| 字段 | 类型 | 说明 |
|---|---|---|
| postTypeOptions | array | 帖子类型选项，结构为 `{label, value}` |
| visibleOptions | array | 可见性选项，结构为 `{label, value}` |
| statusOptions | array | 审核状态选项，结构为 `{label, value}` |

---

### 3.2 官方发帖账号下拉选项

- 方法：`GET`
- 路径：`/api/admin/post/officialMemberOptionselect`
- 参数：无
- 说明：仅返回可用于后台发帖的官方账号（`is_official=1` 且 `status=1` 且未软删）。

#### 响应示例 JSON（成功）

```json
{
    "code": 200,
    "msg": "查询成功",
    "data": [
        {
            "memberId": 10001,
            "nickname": "官方账号A",
            "avatar": "https://cdn.example.com/app/avatar/10001.png",
            "officialLabel": "官方"
        }
    ]
}
```

#### `data` 字段说明

| 字段 | 类型 | 说明 |
|---|---|---|
| memberId | number | 会员 ID（发帖接口 `memberId` 参数值） |
| nickname | string | 昵称 |
| avatar | string | 头像 URL |
| officialLabel | string | 官方标签文案 |

---

### 3.3 后台发布图文帖子

- 方法：`POST`
- 路径：`/api/admin/post/store/imageText`
- 说明：
  - 复用 App 图文发帖校验规则；
  - 后台必须显式传入 `memberId` 指定发帖人；
  - 发帖人必须是官方正常账号（`is_official=1 && status=1 && 未软删`）；
  - 创建后帖子状态会被置为已通过（`status=1`）。

#### Body 参数（JSON）

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| memberId | number | 是 | 官方账号会员 ID，`>=1` |
| content | string | 否 | 内容，最大 `500` 字符；与 `images` 至少有一个非空 |
| images | string[] | 否 | 图片 URL 数组，最多 `9` 张，且必须为图片格式 |
| topics | array | 否 | 话题数组，最多 `3` 个 |
| topics[].id | number | 是（传 topics 时） | 话题 ID，必须存在且为正常状态 |
| topics[].name | string | 是（传 topics 时） | 话题名称，最大 `100` 字符 |
| cover | string | 否 | 封面 URL，最大 `500` 字符 |
| image_show_style | number | 否 | 图片展示样式：`1/2`，默认 `1` |
| article_cover_style | number | 否 | 文章封面样式：`1/2/3`，默认 `1` |
| visible | number | 否 | 可见性：`0/1`，默认 `1` |

#### 请求示例 JSON

```json
{
    "memberId": 10001,
    "content": "春季打卡活动开始啦，欢迎参与！",
    "images": [
        "https://cdn.example.com/app/post/20260312/a1.jpg",
        "https://cdn.example.com/app/post/20260312/a2.png"
    ],
    "topics": [
        {
            "id": 12,
            "name": "春季活动"
        }
    ],
    "visible": 1
}
```

#### 响应示例 JSON（成功）

```json
{
    "code": 200,
    "msg": "发布成功",
    "data": {
        "postId": 350785364900
    }
}
```

#### 响应示例 JSON（失败）

```json
{
    "code": 1201,
    "msg": "请选择发帖人",
    "data": []
}
```

---

### 3.4 后台发布视频帖子

- 方法：`POST`
- 路径：`/api/admin/post/store/video`
- 说明：
  - 复用 App 视频发帖校验规则；
  - `videoUrl` 必填且必须是视频格式；
  - 创建后帖子状态会被置为已通过（`status=1`）。

#### Body 参数（JSON）

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| memberId | number | 是 | 官方账号会员 ID，`>=1` |
| videoUrl | string | 是 | 视频 URL，最大 `500` 字符，必须是视频格式 |
| coverUrl | string | 否 | 封面 URL，最大 `500` 字符 |
| content | string | 否 | 内容，最大 `500` 字符 |
| topics | array | 否 | 话题数组 |
| topics[].id | number | 是（传 topics 时） | 话题 ID |
| topics[].name | string | 是（传 topics 时） | 话题名称，最大 `50` 字符 |
| visible | number | 否 | 可见性：`0/1`，默认 `1` |

#### 请求示例 JSON

```json
{
    "memberId": 10001,
    "videoUrl": "https://cdn.example.com/app/video/20260312/demo.mp4",
    "coverUrl": "https://cdn.example.com/app/video/20260312/demo-cover.jpg",
    "content": "今日训练营精彩片段",
    "topics": [
        {
            "id": 8,
            "name": "训练营"
        }
    ]
}
```

#### 响应示例 JSON（成功）

```json
{
    "code": 200,
    "msg": "发布成功",
    "data": {
        "postId": 350785364901
    }
}
```

#### 响应示例 JSON（失败）

```json
{
    "code": 1201,
    "msg": "请上传视频",
    "data": []
}
```

---

### 3.5 后台发布文章帖子

- 方法：`POST`
- 路径：`/api/admin/post/store/article`
- 说明：
  - 复用 App 文章发帖校验和正文媒体解析逻辑；
  - 文章 `title`、`content` 必填；
  - 创建后帖子状态会被置为已通过（`status=1`）。

#### Body 参数（JSON）

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| memberId | number | 是 | 官方账号会员 ID，`>=1` |
| title | string | 是 | 标题，最大 `50` 字符 |
| content | string | 是 | 正文，最大 `10000` 字符（支持富文本） |
| cover | string | 否 | 封面 URL，最大 `500` 字符 |
| image_show_style | number | 否 | 图片展示样式：`1/2`，默认 `1` |
| article_cover_style | number | 否 | 文章封面样式：`1/2/3`，默认 `1` |
| visible | number | 否 | 可见性：`0/1`，默认 `1` |

#### 请求示例 JSON

```json
{
    "memberId": 10001,
    "title": "3 月社群活动回顾",
    "content": "<p>本月活动精彩回顾...</p><img src=\"https://cdn.example.com/app/post/20260312/r1.jpg\" />",
    "visible": 1
}
```

#### 响应示例 JSON（成功）

```json
{
    "code": 200,
    "msg": "发布成功",
    "data": {
        "postId": 350785364902
    }
}
```

#### 响应示例 JSON（失败）

```json
{
    "code": 1201,
    "msg": "请输入文章内容",
    "data": []
}
```

---

### 3.6 帖子分页列表

- 方法：`GET`
- 路径：`/api/admin/post/list`
- 说明：按筛选条件分页查询帖子管理列表。

#### Query 参数

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| pageNum | number | 否 | 页码，默认 `1` |
| pageSize | number | 否 | 每页条数，默认 `10`，最大 `100` |
| postId | number | 否 | 帖子 ID，精确匹配 |
| memberId | number | 否 | 发帖会员 ID，精确匹配 |
| postType | number | 否 | 帖子类型：`1/2/3` |
| status | number | 否 | 状态：`0/1/2` |
| visible | number | 否 | 可见性：`0/1` |
| isTop | number | 否 | 置顶：`0/1` |
| beginTime | string | 否 | 开始时间，格式如 `2026-03-01 00:00:00` |
| endTime | string | 否 | 结束时间，格式如 `2026-03-31 23:59:59` |

#### 响应示例 JSON（成功）

```json
{
    "code": 200,
    "msg": "查询成功",
    "total": 1,
    "rows": [
        {
            "postId": 350785364902,
            "memberId": 10001,
            "memberNickname": "官方账号A",
            "memberAvatar": "https://cdn.example.com/app/avatar/10001.png",
            "postType": 3,
            "postTypeText": "文章",
            "title": "3 月社群活动回顾",
            "contentSummary": "本月活动精彩回顾...",
            "cover": {
                "url": "https://cdn.example.com/app/post/20260312/r1.jpg",
                "width": 750,
                "height": 420
            },
            "imageShowStyle": 1,
            "articleCoverStyle": 1,
            "isTop": 0,
            "sortScore": 0,
            "visible": 1,
            "visibleText": "公开",
            "status": 1,
            "statusText": "已通过",
            "viewCount": 0,
            "likeCount": 0,
            "commentCount": 0,
            "shareCount": 0,
            "collectionCount": 0,
            "createdAt": "2026-03-12 14:20:30"
        }
    ]
}
```

#### `rows` 字段说明

| 字段 | 类型 | 说明 |
|---|---|---|
| postId | number | 帖子 ID |
| memberId | number | 发帖会员 ID |
| memberNickname | string | 会员昵称 |
| memberAvatar | string | 会员头像 |
| postType | number | 帖子类型：`1/2/3` |
| postTypeText | string | 帖子类型文案 |
| title | string | 标题 |
| contentSummary | string | 列表摘要（去 HTML 且截断） |
| cover | object | 封面对象（可能为空对象） |
| imageShowStyle | number | 图文图片展示样式 |
| articleCoverStyle | number | 文章封面样式 |
| isTop | number | 是否置顶：`0/1` |
| sortScore | number | 排序分 |
| visible | number | 可见性：`0/1` |
| visibleText | string | 可见性文案 |
| status | number | 状态：`0/1/2` |
| statusText | string | 状态文案 |
| viewCount | number | 浏览数 |
| likeCount | number | 点赞数 |
| commentCount | number | 评论数 |
| shareCount | number | 分享数 |
| collectionCount | number | 收藏数 |
| createdAt | string\|null | 创建时间（`Y-m-d H:i:s`） |

#### 响应示例 JSON（失败）

```json
{
    "code": 1201,
    "msg": "参数错误",
    "data": []
}
```

---

### 3.7 帖子详情

- 方法：`GET`
- 路径：`/api/admin/post/{postId}`
- 说明：查询单条帖子详情，返回完整正文、媒体和统计信息。

#### Path 参数

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| postId | number | 是 | 帖子 ID（正整数） |

#### 响应示例 JSON（成功）

```json
{
    "code": 200,
    "msg": "查询成功",
    "data": {
        "postId": 350785364901,
        "memberId": 10001,
        "memberNickname": "官方账号A",
        "memberAvatar": "https://cdn.example.com/app/avatar/10001.png",
        "postType": 2,
        "postTypeText": "视频",
        "title": "今日训练营精彩片段",
        "content": "今日训练营精彩片段",
        "mediaData": [
            {
                "url": "https://cdn.example.com/app/video/20260312/demo.mp4",
                "type": "video",
                "duration": 36
            }
        ],
        "cover": {
            "url": "https://cdn.example.com/app/video/20260312/demo-cover.jpg",
            "width": 750,
            "height": 420
        },
        "imageShowStyle": 1,
        "articleCoverStyle": 1,
        "isTop": 0,
        "sortScore": 0,
        "visible": 1,
        "visibleText": "公开",
        "status": 1,
        "statusText": "已通过",
        "viewCount": 0,
        "likeCount": 0,
        "commentCount": 0,
        "shareCount": 0,
        "collectionCount": 0,
        "createdAt": "2026-03-12 14:20:20",
        "updatedAt": "2026-03-12 14:20:20"
    }
}
```

#### 响应示例 JSON（失败）

```json
{
    "code": 1201,
    "msg": "帖子不存在",
    "data": []
}
```

---

### 3.8 帖子审核（通过/拒绝）

- 方法：`PUT`
- 路径：`/api/admin/post/audit`
- 说明：
  - 仅支持将待审核（`status=0`）帖子审核为已通过（`1`）或已拒绝（`2`）；
  - 非待审核状态不允许重复审核。

#### Body 参数（JSON）

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| postId | number | 是 | 帖子 ID，`>=1` |
| status | number | 是 | 审核状态：`1` 已通过，`2` 已拒绝 |

#### 请求示例 JSON

```json
{
    "postId": 350785364880,
    "status": 1
}
```

#### 响应示例 JSON（成功）

```json
{
    "code": 200,
    "msg": "审核成功",
    "data": []
}
```

#### 响应示例 JSON（失败：重复审核）

```json
{
    "code": 1201,
    "msg": "帖子已审核，不能重复审核",
    "data": []
}
```

#### 响应示例 JSON（失败：参数错误）

```json
{
    "code": 1201,
    "msg": "审核状态值不正确",
    "data": []
}
```
