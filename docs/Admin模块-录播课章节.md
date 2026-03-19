# Admin 模块录播课章节接口文档

## 1. 接口列表总览

| 功能 | 方法 | 路径 |
| --- | --- | --- |
| 录播章节常量选项 | GET | `/api/admin/course/video/chapter/constants` |
| 录播章节分页列表 | GET | `/api/admin/course/video/chapter/list/{courseId}` |
| 录播章节详情 | GET | `/api/admin/course/video/chapter/{chapterId}` |
| 新增录播章节 | POST | `/api/admin/course/video/chapter` |
| 更新录播章节 | PUT | `/api/admin/course/video/chapter` |
| 删除录播章节（单个） | DELETE | `/api/admin/course/video/chapter/{chapterId}` |

## 2. 通用说明

- 鉴权：所有接口都需要 `Authorization: Bearer {token}`
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
  "data": []
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

### 2.2 字段命名说明

- 本模块仅使用新字段：`isFree`、`videoId`、`unlockType`、`unlockDays`、`unlockDate`、`chapterStartTime`、`chapterEndTime`。
- 不再返回旧字段：`isFreeTrial`、`videoIds`、`unlockTime`。

## 3. 详细接口说明

### 3.1 获取录播章节常量选项

- 方法：`GET`
- 路径：`/api/admin/course/video/chapter/constants`
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

### 3.2 获取录播章节分页列表

- 方法：`GET`
- 路径：`/api/admin/course/video/chapter/list/{courseId}`

#### Path 参数

| 参数 | 类型 | 必填 | 说明 |
| --- | --- | --- | --- |
| courseId | number | 是 | 课程 ID（正整数） |

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
  "total": 2,
  "rows": [
    {
      "chapterId": 1001,
      "courseId": 2001,
      "chapterTitle": "第一章：课程导学",
      "chapterSubtitle": "明确学习目标与学习路径",
      "coverImage": "https://cdn.example.com/course/chapter-cover-1001.jpg",
      "videoId": 7100,
      "isFree": 1,
      "unlockType": 1,
      "unlockDays": 0,
      "unlockDate": null,
      "chapterStartTime": "2026-03-20 09:00:00",
      "chapterEndTime": "2026-03-20 10:00:00",
      "status": 1,
      "sortOrder": 1,
      "createTime": "2026-03-18 15:10:00"
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
| coverImage | string\|null | 章节封面地址 |
| videoId | number\|null | 章节绑定的视频 ID（单选） |
| isFree | number | 是否免费：`0` 否，`1` 是 |
| unlockType | number | 解锁类型：`1` 立即解锁，`2` 按天数解锁，`3` 按日期解锁 |
| unlockDays | number | 解锁天数（仅 `unlockType=2` 有业务意义） |
| unlockDate | string\|null | 固定解锁日期（`Y-m-d`，仅 `unlockType=3` 有业务意义） |
| chapterStartTime | string\|null | 章节开始时间（`Y-m-d H:i:s`） |
| chapterEndTime | string\|null | 章节结束时间（`Y-m-d H:i:s`） |
| status | number | 章节状态：`0` 草稿，`1` 上架，`2` 下架 |
| sortOrder | number | 排序值（升序） |
| createTime | string\|null | 创建时间 |

---

### 3.4 获取录播章节详情

- 方法：`GET`
- 路径：`/api/admin/course/video/chapter/{chapterId}`

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
    "chapterId": 1001,
    "courseId": 2001,
    "chapterTitle": "第一章：课程导学",
    "chapterSubtitle": "明确学习目标与学习路径",
    "coverImage": "https://cdn.example.com/course/chapter-cover-1001.jpg",
    "videoId": 7100,
    "isFree": 1,
    "unlockType": 2,
    "unlockDays": 3,
    "unlockDate": null,
    "chapterStartTime": "2026-03-20 09:00:00",
    "chapterEndTime": "2026-03-20 10:00:00",
    "status": 1,
    "sortOrder": 1,
    "createTime": "2026-03-18 15:10:00",
    "updateTime": "2026-03-18 16:30:00"
  }
}
```

#### 响应示例 JSON（失败）

```json
{
  "code": 404,
  "msg": "章节不存在"
}
```

#### `data` 字段说明

| 字段 | 类型 | 说明 |
| --- | --- | --- |
| chapterId | number | 章节 ID |
| courseId | number | 课程 ID |
| chapterTitle | string | 章节标题 |
| chapterSubtitle | string | 章节副标题 |
| coverImage | string\|null | 章节封面地址 |
| videoId | number\|null | 章节绑定的视频 ID（单选） |
| isFree | number | 是否免费：`0` 否，`1` 是 |
| unlockType | number | 解锁类型：`1` 立即解锁，`2` 按天数解锁，`3` 按日期解锁 |
| unlockDays | number | 解锁天数 |
| unlockDate | string\|null | 固定解锁日期（`Y-m-d`） |
| chapterStartTime | string\|null | 章节开始时间（`Y-m-d H:i:s`） |
| chapterEndTime | string\|null | 章节结束时间（`Y-m-d H:i:s`） |
| status | number | 章节状态：`0` 草稿，`1` 上架，`2` 下架 |
| sortOrder | number | 排序值 |
| createTime | string\|null | 创建时间 |
| updateTime | string\|null | 更新时间 |

---

### 3.5 新增录播章节

- 方法：`POST`
- 路径：`/api/admin/course/video/chapter`

#### Body 参数（JSON）

| 参数 | 类型 | 必填 | 说明 |
| --- | --- | --- | --- |
| courseId | number | 是 | 课程 ID；必须为录播课（`play_type=2`）且未删除 |
| chapterTitle | string | 是 | 章节标题，最长 `200` 字符 |
| chapterSubtitle | string | 是 | 章节副标题，最长 `300` 字符 |
| coverImage | string | 是 | 章节封面地址，最长 `500` 字符 |
| videoId | number | 是 | 系统视频 ID；必须存在于 `app_video_system` 且未删除 |
| isFree | number | 是 | 是否免费：`0` 否，`1` 是 |
| unlockType | number | 是 | 解锁类型：`1` 立即解锁，`2` 按天数解锁，`3` 按日期解锁 |
| unlockDays | number | 条件必填 | 当 `unlockType=2` 时必填，整数且 `>=1` |
| unlockDate | string | 条件必填 | 当 `unlockType=3` 时必填，日期格式（`Y-m-d`） |
| chapterStartTime | string | 是 | 章节开始时间（日期时间） |
| chapterEndTime | string | 是 | 章节结束时间（日期时间，且必须晚于 `chapterStartTime`） |
| status | number | 是 | 章节状态：`0` 草稿，`1` 上架，`2` 下架 |

#### 请求示例 JSON

```json
{
  "courseId": 2001,
  "chapterTitle": "第一章：课程导学",
  "chapterSubtitle": "明确学习目标与学习路径",
  "coverImage": "https://cdn.example.com/course/chapter-cover-1001.jpg",
  "videoId": 7100,
  "isFree": 1,
  "unlockType": 2,
  "unlockDays": 3,
  "chapterStartTime": "2026-03-20 09:00:00",
  "chapterEndTime": "2026-03-20 10:00:00",
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

#### 业务行为说明

- 新增时会根据 `videoId` 查询 `app_video_system`。
- 服务端会将视频元数据回填到 `app_chapter_content_video`（单章节仅一条视频内容）。

---

### 3.6 更新录播章节

- 方法：`PUT`
- 路径：`/api/admin/course/video/chapter`

#### Body 参数（JSON）

| 参数 | 类型 | 必填 | 说明 |
| --- | --- | --- | --- |
| chapterId | number | 是 | 章节 ID，且必须属于传入 `courseId` 且未删除 |
| courseId | number | 是 | 课程 ID；必须为录播课（`play_type=2`）且未删除 |
| chapterTitle | string | 是 | 章节标题，最长 `200` 字符 |
| chapterSubtitle | string | 是 | 章节副标题，最长 `300` 字符 |
| coverImage | string | 是 | 章节封面地址，最长 `500` 字符 |
| videoId | number | 是 | 系统视频 ID；必须存在于 `app_video_system` 且未删除 |
| isFree | number | 是 | 是否免费：`0` 否，`1` 是 |
| unlockType | number | 是 | 解锁类型：`1` 立即解锁，`2` 按天数解锁，`3` 按日期解锁 |
| unlockDays | number | 条件必填 | 当 `unlockType=2` 时必填，整数且 `>=1` |
| unlockDate | string | 条件必填 | 当 `unlockType=3` 时必填，日期格式（`Y-m-d`） |
| chapterStartTime | string | 是 | 章节开始时间（日期时间） |
| chapterEndTime | string | 是 | 章节结束时间（日期时间，且必须晚于 `chapterStartTime`） |
| status | number | 是 | 章节状态：`0` 草稿，`1` 上架，`2` 下架 |

#### 请求示例 JSON

```json
{
  "chapterId": 1001,
  "courseId": 2001,
  "chapterTitle": "第一章：课程导学（更新）",
  "chapterSubtitle": "明确学习目标、学习路径与考试要求",
  "coverImage": "https://cdn.example.com/course/chapter-cover-1001-new.jpg",
  "videoId": 7102,
  "isFree": 0,
  "unlockType": 3,
  "unlockDate": "2026-04-01",
  "chapterStartTime": "2026-03-20 09:00:00",
  "chapterEndTime": "2026-03-20 10:30:00",
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

#### 业务行为说明

- 更新接口与创建接口共用同一套字段规则（除 `chapterId`）。
- 更新时也会根据最新 `videoId` 刷新 `app_chapter_content_video` 元数据。

---

### 3.8 删除录播章节（单个）

- 方法：`DELETE`
- 路径：`/api/admin/course/video/chapter/{chapterId}`

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

#### 说明

- 删除接口仅支持单个 `chapterId`，不再支持逗号分隔批量删除。

---

## 4. 关键业务约束

- `courseId` 必须是录播课（`app_course_base.play_type=2`）且课程未软删。
- `videoId` 必须存在于 `app_video_system` 且视频未软删。
- `chapterEndTime` 必须晚于 `chapterStartTime`。
- `status` 允许值：`0=草稿`、`1=上架`、`2=下架`。
- 创建与更新字段规则保持一致（更新额外要求 `chapterId`）。
