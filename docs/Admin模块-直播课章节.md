# Admin 模块直播课章节接口文档

## 1. 接口列表总览

| 功能 | 方法 | 路径 |
| --- | --- | --- |
| 直播章节常量选项 | GET | `/api/admin/course/live/chapter/constants` |
| 直播章节分页列表 | GET | `/api/admin/course/live/chapter/list/{courseId}` |
| 直播章节详情 | GET | `/api/admin/course/live/chapter/{chapterId}` |
| 新增直播章节 | POST | `/api/admin/course/live/chapter` |
| 更新直播章节 | PUT | `/api/admin/course/live/chapter` |
| 删除直播章节（单个） | DELETE | `/api/admin/course/live/chapter/{chapterId}` |

## 2. 通用说明

- 鉴权：所有接口都需要 `Authorization: Bearer {token}`。
- 请求头建议：

```http
Content-Type: application/json
Accept: application/json
Authorization: Bearer {token}
```

- 课程类型约束：`courseId` 必须是直播课（`play_type=1`）课程。
- 创建与更新使用同一套字段规则，不兼容旧字段。
- 删除接口仅支持单个 `chapterId`，不支持批量删除。

### 2.1 字段与规则约定

- 仅使用新字段：`courseId`、`chapterTitle`、`chapterSubtitle`、`coverImage`、`liveRoomId`、`isFree`、`unlockType`、`unlockDays`、`unlockDate`、`chapterStartTime`、`chapterEndTime`、`status`。
- 不再使用旧字段：`roomId`、`liveStartTime`、`liveEndTime`。
- 条件必填规则：
  - `unlockType=2` 时，`unlockDays` 必填；
  - `unlockType=3` 时，`unlockDate` 必填。

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

#### 失败响应（参数校验/业务异常）

```json
{
  "code": 1201,
  "msg": "章节标题不能为空。",
  "data": []
}
```

## 3. 详细接口说明

### 3.1 获取直播章节常量选项

- 方法：`GET`
- 路径：`/api/admin/course/live/chapter/constants`
- 参数：无

#### 响应示例 JSON

```json
{
  "code": 200,
  "msg": "查询成功",
  "data": {
    "isFreeOptions": [
      {
        "label": "免费",
        "value": 1
      },
      {
        "label": "付费",
        "value": 0
      }
    ],
    "unlockTypeOptions": [
      {
        "label": "立即解锁",
        "value": 1
      },
      {
        "label": "按天数解锁",
        "value": 2
      },
      {
        "label": "按日期解锁",
        "value": 3
      }
    ],
    "statusOptions": [
      {
        "label": "草稿",
        "value": 0
      },
      {
        "label": "上架",
        "value": 1
      },
      {
        "label": "下架",
        "value": 2
      }
    ]
  }
}
```

#### `data` 字段说明

| 字段 | 类型 | 说明 |
| --- | --- | --- |
| isFreeOptions | array | 是否免费选项 |
| unlockTypeOptions | array | 解锁类型选项 |
| statusOptions | array | 状态选项 |

每个选项对象结构：

| 字段 | 类型 | 说明 |
| --- | --- | --- |
| label | string | 显示文本 |
| value | number | 枚举值 |

---

### 3.2 获取直播章节分页列表

- 方法：`GET`
- 路径：`/api/admin/course/live/chapter/list/{courseId}`

#### Path 参数

| 参数 | 类型 | 必填 | 说明 |
| --- | --- | --- | --- |
| courseId | number | 是 | 课程 ID（直播课课程） |

#### Query 参数

| 参数 | 类型 | 必填 | 说明 |
| --- | --- | --- | --- |
| pageNum | number | 否 | 页码，默认 `1` |
| pageSize | number | 否 | 每页条数，默认 `10` |

#### 响应示例 JSON

```json
{
  "code": 200,
  "msg": "查询成功",
  "total": 1,
  "rows": [
    {
      "chapterId": 3001,
      "courseId": 2001,
      "chapterTitle": "第一节：直播导学",
      "chapterSubtitle": "课前说明与学习目标",
      "coverImage": "https://cdn.example.com/live/chapter-cover-3001.jpg",
      "liveRoomId": "9001",
      "isFree": 1,
      "unlockType": 1,
      "unlockDays": 0,
      "unlockDate": null,
      "chapterStartTime": "2026-03-25 19:00:00",
      "chapterEndTime": "2026-03-25 20:30:00",
      "liveStatus": 0,
      "liveStatusText": "未开始",
      "sortOrder": 1,
      "status": 1,
      "createTime": "2026-03-20 14:20:00"
    }
  ]
}
```

#### `rows` 字段说明

| 字段 | 类型 | 说明 |
| --- | --- | --- |
| chapterId | number | 章节 ID |
| courseId | number | 课程 ID |
| chapterTitle | string | 章节标题 |
| chapterSubtitle | string | 章节副标题 |
| coverImage | string\|null | 章节封面 |
| liveRoomId | string\|null | 关联直播间 ID（内容表快照字段 `live_room_id`） |
| isFree | number | 是否免费：`0` 否，`1` 是 |
| unlockType | number | 解锁类型：`1` 立即解锁，`2` 按天数解锁，`3` 按日期解锁 |
| unlockDays | number | 解锁天数（`unlockType=2` 时有业务意义） |
| unlockDate | string\|null | 固定解锁日期（`Y-m-d`，`unlockType=3` 时有业务意义） |
| chapterStartTime | string\|null | 章节开始时间（`Y-m-d H:i:s`） |
| chapterEndTime | string\|null | 章节结束时间（`Y-m-d H:i:s`） |
| liveStatus | number\|null | 直播状态：`0` 未开始，`1` 直播中，`2` 已结束，`3` 已取消 |
| liveStatusText | string\|null | 直播状态文本 |
| sortOrder | number | 排序值（升序） |
| status | number | 章节状态：`0` 草稿，`1` 上架，`2` 下架 |
| createTime | string\|null | 创建时间 |

---

### 3.3 获取直播章节详情

- 方法：`GET`
- 路径：`/api/admin/course/live/chapter/{chapterId}`

#### Path 参数

| 参数 | 类型 | 必填 | 说明 |
| --- | --- | --- | --- |
| chapterId | number | 是 | 章节 ID（正整数） |

#### 响应示例 JSON（成功）

```json
{
  "code": 200,
  "msg": "查询成功",
  "data": {
    "chapterId": 3001,
    "courseId": 2001,
    "chapterTitle": "第一节：直播导学",
    "chapterSubtitle": "课前说明与学习目标",
    "coverImage": "https://cdn.example.com/live/chapter-cover-3001.jpg",
    "liveRoomId": "9001",
    "isFree": 1,
    "unlockType": 2,
    "unlockDays": 3,
    "unlockDate": null,
    "chapterStartTime": "2026-03-25 19:00:00",
    "chapterEndTime": "2026-03-25 20:30:00",
    "liveStatus": 0,
    "liveStatusText": "未开始",
    "sortOrder": 1,
    "status": 1,
    "createTime": "2026-03-20 14:20:00",
    "updateTime": "2026-03-20 16:00:00"
  }
}
```

#### 响应示例 JSON（失败）

```json
{
  "code": 1201,
  "msg": "章节不存在"
}
```

#### `data` 字段说明

详情字段与列表一致，额外返回：

| 字段 | 类型 | 说明 |
| --- | --- | --- |
| updateTime | string\|null | 更新时间 |

---

### 3.4 新增直播章节

- 方法：`POST`
- 路径：`/api/admin/course/live/chapter`

#### 请求体参数

| 参数 | 类型 | 必填 | 说明 |
| --- | --- | --- | --- |
| courseId | number | 是 | 课程 ID（必须为直播课） |
| chapterTitle | string | 是 | 章节标题，最大 200 字符 |
| chapterSubtitle | string | 是 | 章节副标题，最大 300 字符 |
| coverImage | string | 是 | 章节封面地址，最大 500 字符 |
| liveRoomId | number | 是 | 直播间 ID（`app_live_room.room_id`） |
| isFree | number | 是 | 是否免费：`0` 否，`1` 是 |
| unlockType | number | 是 | 解锁类型：`1` 立即解锁，`2` 按天数解锁，`3` 按日期解锁 |
| unlockDays | number | 条件必填 | `unlockType=2` 时必填，且 `>=1` |
| unlockDate | string | 条件必填 | `unlockType=3` 时必填，日期格式 |
| chapterStartTime | string | 是 | 章节开始时间（日期时间） |
| chapterEndTime | string | 是 | 章节结束时间（必须晚于开始时间） |
| status | number | 是 | 章节状态：`0` 草稿，`1` 上架，`2` 下架 |

#### 请求示例 JSON

```json
{
  "courseId": 2001,
  "chapterTitle": "第一节：直播导学",
  "chapterSubtitle": "课前说明与学习目标",
  "coverImage": "https://cdn.example.com/live/chapter-cover-3001.jpg",
  "liveRoomId": 9001,
  "isFree": 1,
  "unlockType": 1,
  "unlockDays": null,
  "unlockDate": null,
  "chapterStartTime": "2026-03-25 19:00:00",
  "chapterEndTime": "2026-03-25 20:30:00",
  "status": 1
}
```

#### 响应示例 JSON（成功）

```json
{
  "code": 200,
  "msg": "操作成功",
  "data": []
}
```

#### 响应示例 JSON（失败：条件必填未满足）

```json
{
  "code": 1201,
  "msg": "按日期解锁时，解锁日期不能为空。",
  "data": []
}
```

---

### 3.5 更新直播章节

- 方法：`PUT`
- 路径：`/api/admin/course/live/chapter`

#### 请求体参数

除新增参数外，额外需要：

| 参数 | 类型 | 必填 | 说明 |
| --- | --- | --- | --- |
| chapterId | number | 是 | 待更新章节 ID（必须属于当前 `courseId`） |

其余参数与“新增直播章节”一致，创建与更新必填规则完全相同。

#### 请求示例 JSON

```json
{
  "chapterId": 3001,
  "courseId": 2001,
  "chapterTitle": "第一节：直播导学（更新）",
  "chapterSubtitle": "课前说明与学习目标",
  "coverImage": "https://cdn.example.com/live/chapter-cover-3001-v2.jpg",
  "liveRoomId": 9002,
  "isFree": 0,
  "unlockType": 2,
  "unlockDays": 5,
  "unlockDate": null,
  "chapterStartTime": "2026-03-26 19:00:00",
  "chapterEndTime": "2026-03-26 20:30:00",
  "status": 2
}
```

#### 响应示例 JSON（成功）

```json
{
  "code": 200,
  "msg": "操作成功",
  "data": []
}
```

---

### 3.6 删除直播章节（单个）

- 方法：`DELETE`
- 路径：`/api/admin/course/live/chapter/{chapterId}`

#### Path 参数

| 参数 | 类型 | 必填 | 说明 |
| --- | --- | --- | --- |
| chapterId | number | 是 | 章节 ID（正整数） |

#### 响应示例 JSON（成功）

```json
{
  "code": 200,
  "msg": "操作成功",
  "data": []
}
```

#### 响应示例 JSON（失败）

```json
{
  "code": 1201,
  "msg": "数据不存在",
  "data": []
}
```

## 4. 附录 / 变更说明

- `PUT /api/admin/course/live/chapter/changeStatus` 已移除，章节状态统一在更新接口 `PUT /api/admin/course/live/chapter` 中处理。
- 下面 3 个路由当前为预留状态（未开放），本文件不提供其详细请求/响应定义：
  - `PUT /api/admin/course/live/chapter/syncLiveStatus/{chapterId}`
  - `GET /api/admin/course/live/chapter/playback/{chapterId}`
  - `PUT /api/admin/course/live/chapter/syncPlayback/{chapterId}`
- 新增/更新时，前端仅传 `liveRoomId`；服务端会基于直播间表回填最小直播元数据到 `app_chapter_content_live`（如 `live_room_id/live_platform/live_push_url/live_pull_url/live_cover`）。
