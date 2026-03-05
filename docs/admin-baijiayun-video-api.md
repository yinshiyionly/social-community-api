# Admin 模块百家云视频库接口文档

## 1. 接口列表总览
| 功能 | 方法 | 路径 |
| --- | --- | --- |
| 百家云视频常量选项 | GET | `/api/admin/video/baijiayun/constants` |
| 百家云视频分页列表 | GET | `/api/admin/video/baijiayun/list` |
| 百家云视频详情 | GET | `/api/admin/video/baijiayun/{videoId}` |
| 新增百家云视频 | POST | `/api/admin/video/baijiayun` |
| 更新百家云视频 | PUT | `/api/admin/video/baijiayun` |
| 删除百家云视频（支持批量） | DELETE | `/api/admin/video/baijiayun/{videoIds}` |

## 2. 通用说明
- 鉴权：所有接口都需要 `Authorization: Bearer {token}`（`system.auth` 中间件）
- 路由前缀：`/api/admin`
- 请求头建议：

```http
Content-Type: application/json
Accept: application/json
Authorization: Bearer {token}
```

### 2.1 通用响应示例

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
  "msg": "查询成功",
  "data": {}
}
```

#### 失败响应
```json
{
  "code": 6000,
  "msg": "操作失败",
  "data": []
}
```

## 3. 详细接口说明

### 3.1 获取百家云视频常量选项
- 方法：`GET`
- 路径：`/api/admin/video/baijiayun/constants`
- 参数：无
- 说明：返回状态、发布状态和来源选项，用于前端筛选与表单渲染。

#### 响应示例 JSON
```json
{
  "code": 200,
  "msg": "查询成功",
  "data": {
    "statusOptions": [
      {
        "label": "上传中",
        "value": 10
      },
      {
        "label": "转码中",
        "value": 20
      },
      {
        "label": "转码失败",
        "value": 30
      },
      {
        "label": "转码超时",
        "value": 31
      },
      {
        "label": "上传超时",
        "value": 32
      },
      {
        "label": "转码成功",
        "value": 100
      }
    ],
    "publishStatusOptions": [
      {
        "label": "未发布",
        "value": 0
      },
      {
        "label": "已发布",
        "value": 1
      }
    ],
    "sourceOptions": [
      {
        "label": "百家云",
        "value": "baijiayun"
      }
    ]
  }
}
```

#### `data` 字段说明
| 字段 | 类型 | 说明 |
| --- | --- | --- |
| statusOptions | array | 转码状态选项 |
| publishStatusOptions | array | 发布状态选项 |
| sourceOptions | array | 来源选项 |

每个选项对象结构：

| 字段 | 类型 | 说明 |
| --- | --- | --- |
| label | string | 显示文本 |
| value | number\|string | 枚举值 |

---

### 3.2 获取百家云视频分页列表
- 方法：`GET`
- 路径：`/api/admin/video/baijiayun/list`

#### Query 参数
| 参数 | 类型 | 必填 | 说明 |
| --- | --- | --- | --- |
| pageNum | number | 否 | 页码，默认 `1` |
| pageSize | number | 否 | 每页条数，默认 `10` |
| videoId | number | 否 | 视频 ID（精确匹配） |
| name | string | 否 | 视频名称（模糊匹配） |
| status | number | 否 | 转码状态：`10/20/30/31/32/100` |
| publishStatus | number | 否 | 发布状态：`0` 未发布，`1` 已发布 |
| beginTime | string | 否 | 创建时间起始（`created_at >= beginTime`） |
| endTime | string | 否 | 创建时间结束（`created_at <= endTime`） |

#### 响应示例 JSON
```json
{
  "code": 200,
  "msg": "查询成功",
  "total": 1,
  "rows": [
    {
      "videoId": 123456,
      "name": "百家云示例视频",
      "status": 100,
      "statusText": "转码成功",
      "publishStatus": 1,
      "publishStatusText": "已发布",
      "totalSize": "1048576",
      "totalSizeText": "1 MB",
      "length": 120,
      "lengthText": "00:02:00",
      "prefaceUrl": "https://cdn.example.com/video/123456-cover.jpg",
      "playUrl": "https://cdn.example.com/video/123456.m3u8",
      "width": 1920,
      "height": 1080,
      "uploadTime": "2026-03-05 19:20:00",
      "createdAt": "2026-03-05 19:20:00"
    }
  ]
}
```

#### `rows` 字段说明
| 字段 | 类型 | 说明 |
| --- | --- | --- |
| videoId | number | 视频 ID |
| name | string | 视频名称 |
| status | number | 转码状态 |
| statusText | string | 转码状态文本 |
| publishStatus | number | 发布状态：`0` 未发布，`1` 已发布 |
| publishStatusText | string | 发布状态文本 |
| totalSize | string | 视频大小（字节字符串） |
| totalSizeText | string | 格式化大小文本（如 `1 MB`） |
| length | number | 时长（秒） |
| lengthText | string | 格式化时长（`HH:mm:ss`） |
| prefaceUrl | string\|null | 封面地址 |
| playUrl | string\|null | 播放地址 |
| width | number | 视频宽度 |
| height | number | 视频高度 |
| uploadTime | string\|null | 上传时间（同创建时间） |
| createdAt | string\|null | 创建时间 |

---

### 3.3 获取百家云视频详情
- 方法：`GET`
- 路径：`/api/admin/video/baijiayun/{videoId}`

#### Path 参数
| 参数 | 类型 | 必填 | 说明 |
| --- | --- | --- | --- |
| videoId | number | 是 | 视频 ID（正整数） |

#### 响应示例 JSON（成功）
```json
{
  "code": 200,
  "msg": "查询成功",
  "data": {
    "videoId": 123456,
    "name": "百家云示例视频",
    "status": 100,
    "statusText": "转码成功",
    "publishStatus": 1,
    "publishStatusText": "已发布",
    "totalSize": "1048576",
    "totalSizeText": "1 MB",
    "length": 120,
    "lengthText": "00:02:00",
    "prefaceUrl": "https://cdn.example.com/video/123456-cover.jpg",
    "playUrl": "https://cdn.example.com/video/123456.m3u8",
    "width": 1920,
    "height": 1080,
    "uploadTime": "2026-03-05 19:20:00",
    "createdAt": "2026-03-05 19:20:00",
    "updatedAt": "2026-03-05 20:00:00"
  }
}
```

#### 响应示例 JSON（失败）
```json
{
  "code": 6000,
  "msg": "视频不存在",
  "data": []
}
```

#### `data` 字段说明
| 字段 | 类型 | 说明 |
| --- | --- | --- |
| videoId | number | 视频 ID |
| name | string | 视频名称 |
| status | number | 转码状态 |
| statusText | string | 转码状态文本 |
| publishStatus | number | 发布状态 |
| publishStatusText | string | 发布状态文本 |
| totalSize | string | 视频大小（字节字符串） |
| totalSizeText | string | 格式化大小文本 |
| length | number | 时长（秒） |
| lengthText | string | 格式化时长（`HH:mm:ss`） |
| prefaceUrl | string\|null | 封面地址 |
| playUrl | string\|null | 播放地址 |
| width | number | 视频宽度 |
| height | number | 视频高度 |
| uploadTime | string\|null | 上传时间（同创建时间） |
| createdAt | string\|null | 创建时间 |
| updatedAt | string\|null | 更新时间 |

---

### 3.4 新增百家云视频
- 方法：`POST`
- 路径：`/api/admin/video/baijiayun`
- 说明：`videoId` 需要外部传入，且必须唯一（软删除记录除外）。

#### Body 参数（JSON）
| 参数 | 类型 | 必填 | 说明 |
| --- | --- | --- | --- |
| videoId | number | 是 | 视频 ID，`>= 1`，且在 `app_video_baijiayun.video_id` 中唯一 |
| name | string | 是 | 视频标题，最长 `255` 字符 |
| status | number | 否 | 转码状态：`10/20/30/31/32/100`，默认 `10` |
| totalSize | string | 否 | 视频大小（字节字符串），最长 `50` 字符，默认 `"0"` |
| prefaceUrl | string | 否 | 封面地址，最长 `1024` 字符 |
| playUrl | string | 否 | 播放地址，最长 `512` 字符 |
| length | number | 否 | 时长（秒），`>= 0`，默认 `0` |
| width | number | 否 | 视频宽度，`>= 0`，默认 `0` |
| height | number | 否 | 视频高度，`>= 0`，默认 `0` |
| publishStatus | number | 否 | 发布状态：`0` 未发布，`1` 已发布，默认 `0` |

#### 请求示例 JSON
```json
{
  "videoId": 123456,
  "name": "百家云新增视频",
  "status": 10,
  "totalSize": "2097152",
  "prefaceUrl": "https://cdn.example.com/video/new-cover.jpg",
  "playUrl": "https://cdn.example.com/video/new.m3u8",
  "length": 300,
  "width": 1920,
  "height": 1080,
  "publishStatus": 0
}
```

#### 响应示例 JSON（成功）
```json
{
  "code": 200,
  "msg": "新增成功",
  "data": {
    "videoId": 123456
  }
}
```

#### 响应示例 JSON（失败：ID 已存在）
```json
{
  "code": 6000,
  "msg": "视频ID已存在",
  "data": []
}
```

---

### 3.5 更新百家云视频
- 方法：`PUT`
- 路径：`/api/admin/video/baijiayun`

#### Body 参数（JSON）
| 参数 | 类型 | 必填 | 说明 |
| --- | --- | --- | --- |
| videoId | number | 是 | 视频 ID，`>= 1` |
| name | string | 否 | 视频标题，最长 `255` 字符 |
| status | number | 否 | 转码状态：`10/20/30/31/32/100` |
| totalSize | string | 否 | 视频大小（字节字符串），最长 `50` 字符 |
| prefaceUrl | string\|null | 否 | 封面地址，最长 `1024` 字符，可传 `null` |
| playUrl | string\|null | 否 | 播放地址，最长 `512` 字符，可传 `null` |
| length | number | 否 | 时长（秒），`>= 0` |
| width | number | 否 | 视频宽度，`>= 0` |
| height | number | 否 | 视频高度，`>= 0` |
| publishStatus | number | 否 | 发布状态：`0` 未发布，`1` 已发布 |

#### 请求示例 JSON
```json
{
  "videoId": 123456,
  "name": "百家云新增视频（修订版）",
  "status": 100,
  "publishStatus": 1,
  "length": 320
}
```

#### 响应示例 JSON（成功）
```json
{
  "code": 200,
  "msg": "修改成功",
  "data": []
}
```

#### 响应示例 JSON（失败）
```json
{
  "code": 6000,
  "msg": "视频不存在",
  "data": []
}
```

---

### 3.6 删除百家云视频（支持批量）
- 方法：`DELETE`
- 路径：`/api/admin/video/baijiayun/{videoIds}`
- 说明：
  - 支持批量删除，`videoIds` 使用英文逗号分隔，例如：`123456,123457,123458`。
  - 逻辑删除（软删除）。
  - 删除前会检查 `admin_video_chapter_content.video_id` 引用，已被章节使用的视频不能删除。

#### Path 参数
| 参数 | 类型 | 必填 | 说明 |
| --- | --- | --- | --- |
| videoIds | string | 是 | 视频 ID 列表，逗号分隔 |

#### 响应示例 JSON（成功）
```json
{
  "code": 200,
  "msg": "删除成功",
  "data": []
}
```

#### 响应示例 JSON（失败：被章节引用）
```json
{
  "code": 6000,
  "msg": "视频已被课程章节使用，无法删除",
  "data": []
}
```

#### 响应示例 JSON（失败：参数错误）
```json
{
  "code": 6000,
  "msg": "参数错误",
  "data": []
}
```

#### 响应示例 JSON（失败：视频不存在）
```json
{
  "code": 6000,
  "msg": "删除失败，视频不存在",
  "data": []
}
```
