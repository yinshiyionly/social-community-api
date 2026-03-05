# Admin 模块直播间接口文档

## 1. 接口列表总览
| 功能 | 方法 | 路径 |
| --- | --- | --- |
| 直播间分页列表 | GET | `/api/admin/live/room/list` |
| 直播间详情 | GET | `/api/admin/live/room/{roomId}` |
| 新增直播间 | POST | `/api/admin/live/room` |
| 更新直播间 | PUT | `/api/admin/live/room` |
| 修改直播间状态 | PUT | `/api/admin/live/room/changeStatus` |
| 删除直播间（不支持批量） | DELETE | `/api/admin/live/room/{roomId}` |

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

### 2.2 枚举说明

#### 直播类型 `liveType`
| 值 | 说明 |
| --- | --- |
| 1 | 真实直播 |
| 2 | 伪直播 |

#### 直播状态 `liveStatus`
| 值 | 说明 |
| --- | --- |
| 0 | 未开始 |
| 1 | 直播中 |
| 2 | 已结束 |
| 3 | 已取消 |

#### 启用状态 `status`
| 值 | 说明 |
| --- | --- |
| 0 | 禁用 |
| 1 | 启用 |

#### 直播平台 `livePlatform`
| 值 | 说明 |
| --- | --- |
| custom | 自定义 |
| baijiayun | 百家云 |
| aliyun | 阿里云 |
| tencent | 腾讯云 |
| agora | 声网 |

## 3. 详细接口说明

### 3.1 获取直播间分页列表
- 方法：`GET`
- 路径：`/api/admin/live/room/list`

#### Query 参数
| 参数 | 类型 | 必填 | 说明 |
| --- | --- | --- | --- |
| pageNum | number | 否 | 页码，默认 `1` |
| pageSize | number | 否 | 每页条数，默认 `10` |
| liveType | number | 否 | 直播类型：`1` 真实直播，`2` 伪直播 |
| liveStatus | number | 否 | 直播状态：`0` 未开始，`1` 直播中，`2` 已结束，`3` 已取消 |
| status | number | 否 | 启用状态：`0` 禁用，`1` 启用 |
| roomTitle | string | 否 | 直播间标题（模糊搜索） |
| anchorName | string | 否 | 主播名称（模糊搜索） |
| livePlatform | string | 否 | 直播平台：`custom/baijiayun/aliyun/tencent/agora` |
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
      "roomId": 1001,
      "roomTitle": "中医公开课直播间",
      "roomCover": "https://cdn.example.com/live/room-1001-cover.jpg",
      "liveType": 1,
      "livePlatform": "baijiayun",
      "anchorName": "张老师",
      "scheduledStartTime": "2026-03-10 19:30:00",
      "scheduledEndTime": "2026-03-10 21:00:00",
      "liveStatus": 0,
      "liveStatusText": "未开始",
      "allowChat": 1,
      "allowGift": 1,
      "status": 1,
      "currentOnlineCount": 0,
      "totalViewerCount": 0,
      "createdAt": "2026-03-05 19:20:00"
    }
  ]
}
```

#### `rows` 字段说明
| 字段 | 类型 | 说明 |
| --- | --- | --- |
| roomId | number | 直播间 ID |
| roomTitle | string | 直播间标题 |
| roomCover | string\|null | 直播间封面 |
| liveType | number | 直播类型 |
| livePlatform | string\|null | 直播平台 |
| anchorName | string\|null | 主播名称 |
| scheduledStartTime | string\|null | 计划开始时间 |
| scheduledEndTime | string\|null | 计划结束时间 |
| liveStatus | number | 直播状态 |
| liveStatusText | string | 直播状态文本 |
| allowChat | number | 是否允许聊天（0/1） |
| allowGift | number | 是否允许送礼（0/1） |
| status | number | 启用状态（0/1） |
| currentOnlineCount | number | 当前在线人数 |
| totalViewerCount | number | 累计观看人数 |
| createdAt | string\|null | 创建时间 |

---

### 3.2 获取直播间详情
- 方法：`GET`
- 路径：`/api/admin/live/room/{roomId}`

#### Path 参数
| 参数 | 类型 | 必填 | 说明 |
| --- | --- | --- | --- |
| roomId | number | 是 | 直播间 ID（正整数） |

#### 响应示例 JSON（成功）
```json
{
  "code": 200,
  "msg": "查询成功",
  "data": {
    "roomId": 1001,
    "roomTitle": "中医公开课直播间",
    "roomCover": "https://cdn.example.com/live/room-1001-cover.jpg",
    "roomIntro": "零基础直播课",
    "liveType": 1,
    "livePlatform": "baijiayun",
    "thirdPartyRoomId": 998877,
    "pushUrl": "rtmp://push.example.com/live/1001",
    "pullUrl": "https://pull.example.com/live/1001.m3u8",
    "videoUrl": null,
    "anchorId": 2001,
    "anchorName": "张老师",
    "anchorAvatar": "https://cdn.example.com/avatar/2001.png",
    "scheduledStartTime": "2026-03-10 19:30:00",
    "scheduledEndTime": "2026-03-10 21:00:00",
    "actualStartTime": null,
    "actualEndTime": null,
    "liveDuration": 90,
    "liveStatus": 0,
    "liveStatusText": "未开始",
    "allowChat": 1,
    "allowGift": 1,
    "allowLike": 1,
    "password": null,
    "extConfig": [],
    "status": 1,
    "totalViewerCount": 0,
    "maxOnlineCount": 0,
    "currentOnlineCount": 0,
    "likeCount": 0,
    "messageCount": 0,
    "createdAt": "2026-03-05 19:20:00",
    "updatedAt": "2026-03-05 19:20:00"
  }
}
```

#### 响应示例 JSON（失败）
```json
{
  "code": 6000,
  "msg": "直播间不存在",
  "data": []
}
```

#### `data` 字段说明
| 字段 | 类型 | 说明 |
| --- | --- | --- |
| roomId | number | 直播间 ID |
| roomTitle | string | 直播间标题 |
| roomCover | string\|null | 直播间封面 |
| roomIntro | string\|null | 直播间简介 |
| liveType | number | 直播类型 |
| livePlatform | string\|null | 直播平台 |
| thirdPartyRoomId | number\|null | 第三方直播间 ID |
| pushUrl | string\|null | 推流地址 |
| pullUrl | string\|null | 拉流地址 |
| videoUrl | string\|null | 伪直播视频地址 |
| anchorId | number\|null | 主播 ID |
| anchorName | string\|null | 主播名称 |
| anchorAvatar | string\|null | 主播头像 |
| scheduledStartTime | string\|null | 计划开始时间 |
| scheduledEndTime | string\|null | 计划结束时间 |
| actualStartTime | string\|null | 实际开始时间 |
| actualEndTime | string\|null | 实际结束时间 |
| liveDuration | number\|null | 直播时长（分钟） |
| liveStatus | number | 直播状态 |
| liveStatusText | string | 直播状态文本 |
| allowChat | number | 是否允许聊天（0/1） |
| allowGift | number | 是否允许送礼（0/1） |
| allowLike | number | 是否允许点赞（0/1） |
| password | string\|null | 房间密码（有值时脱敏显示 `******`） |
| extConfig | object\|array\|null | 扩展配置 |
| status | number | 启用状态（0/1） |
| totalViewerCount | number | 累计观看人数 |
| maxOnlineCount | number | 峰值在线人数 |
| currentOnlineCount | number | 当前在线人数 |
| likeCount | number | 点赞数 |
| messageCount | number | 消息数 |
| createdAt | string\|null | 创建时间 |
| updatedAt | string\|null | 更新时间 |

---

### 3.3 新增直播间
- 方法：`POST`
- 路径：`/api/admin/live/room`
- 说明：
  - 创建时会调用第三方直播服务（百家云）创建房间；第三方失败会返回通用失败信息。
  - `liveType=2`（伪直播）时，`videoUrl`、`scheduledStartTime`、`scheduledEndTime` 为必填，且开始时间必须晚于当前时间。

#### Body 参数（JSON）
| 参数 | 类型 | 必填 | 说明 |
| --- | --- | --- | --- |
| roomTitle | string | 是 | 直播间标题，最长 `200` 字符 |
| liveType | number | 是 | 直播类型：`1` 真实直播，`2` 伪直播 |
| scheduledStartTime | string | 否 | 计划开始时间，日期格式；伪直播时必填且需晚于当前时间 |
| scheduledEndTime | string | 否 | 计划结束时间，日期格式；伪直播时必填且需晚于开始时间 |
| videoUrl | string | 条件必填 | 伪直播视频地址（`liveType=2` 时必填），最长 `500` 字符 |

#### 请求示例 JSON（真实直播）
```json
{
  "roomTitle": "中医公开课直播间",
  "liveType": 1,
  "scheduledStartTime": "2026-03-10 19:30:00",
  "scheduledEndTime": "2026-03-10 21:00:00"
}
```

#### 请求示例 JSON（伪直播）
```json
{
  "roomTitle": "伪直播示例",
  "liveType": 2,
  "videoUrl": "https://cdn.example.com/live/mock-1002.mp4",
  "scheduledStartTime": "2026-03-10 19:30:00",
  "scheduledEndTime": "2026-03-10 21:00:00"
}
```

#### 响应示例 JSON（成功）
```json
{
  "code": 200,
  "msg": "新增成功",
  "data": []
}
```

#### 响应示例 JSON（失败）
```json
{
  "code": 6000,
  "msg": "操作失败，请稍后重试",
  "data": []
}
```

---

### 3.4 更新直播间
- 方法：`PUT`
- 路径：`/api/admin/live/room`
- 说明：
  - 直播进行中时，不允许修改 `scheduledStartTime` / `scheduledEndTime`（传入非空值会报错）。

#### Body 参数（JSON）
| 参数 | 类型 | 必填 | 说明 |
| --- | --- | --- | --- |
| roomId | number | 是 | 直播间 ID，`>= 1` |
| roomTitle | string | 否 | 直播间标题，最长 `200` 字符 |
| scheduledStartTime | string | 否 | 计划开始时间，日期格式 |
| scheduledEndTime | string | 否 | 计划结束时间，日期格式，且需晚于 `scheduledStartTime` |

#### 请求示例 JSON
```json
{
  "roomId": 1001,
  "roomTitle": "中医公开课直播间（修订版）",
  "scheduledStartTime": "2026-03-10 20:00:00",
  "scheduledEndTime": "2026-03-10 21:30:00"
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

#### 响应示例 JSON（失败：直播间不存在）
```json
{
  "code": 6000,
  "msg": "直播间不存在",
  "data": []
}
```

#### 响应示例 JSON（失败：直播中限制）
```json
{
  "code": 6000,
  "msg": "直播进行中，无法修改",
  "data": []
}
```

---

### 3.5 修改直播间状态
- 方法：`PUT`
- 路径：`/api/admin/live/room/changeStatus`

#### Body 参数（JSON）
| 参数 | 类型 | 必填 | 说明 |
| --- | --- | --- | --- |
| roomId | number | 是 | 直播间 ID，`>= 1` |
| status | number | 是 | 启用状态：`0` 禁用，`1` 启用 |

#### 请求示例 JSON
```json
{
  "roomId": 1001,
  "status": 1
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
  "msg": "直播间不存在",
  "data": []
}
```

---

### 3.6 删除直播间（不支持批量）
- 方法：`DELETE`
- 路径：`/api/admin/live/room/{roomId}`
- 说明：
  - 仅支持单个删除，不支持批量。
  - 逻辑删除（软删除）。
  - 若直播间已被直播课程章节使用，不允许删除。
  - 若直播间处于直播中，不允许删除。

#### Path 参数
| 参数 | 类型 | 必填 | 说明 |
| --- | --- | --- | --- |
| roomId | number | 是 | 直播间 ID（正整数） |

#### 响应示例 JSON（成功）
```json
{
  "code": 200,
  "msg": "删除成功",
  "data": []
}
```

#### 响应示例 JSON（失败：被课程章节引用）
```json
{
  "code": 6000,
  "msg": "直播间已被直播课程章节使用，无法删除",
  "data": []
}
```

#### 响应示例 JSON（失败：直播中）
```json
{
  "code": 6000,
  "msg": "直播间\"中医公开课直播间\"正在直播中，无法删除",
  "data": []
}
```

#### 响应示例 JSON（失败：不存在）
```json
{
  "code": 6000,
  "msg": "直播间不存在",
  "data": []
}
```
