# Admin 模块系统消息接口文档

## 1. 接口列表总览

| 功能 | 方法 | 路径 |
|---|---|---|
| 官方发送者下拉选项 | GET | `/api/admin/message/system/senderOptions` |
| 发送系统消息 | POST | `/api/admin/message/system/send` |
| 系统消息分页列表 | GET | `/api/admin/message/system/list` |

## 2. 通用说明

- 鉴权：所有接口均需通过 `system.auth` 中间件鉴权。
- 非分页响应结构：`code`、`msg`、`data`。
- 分页响应结构：`code`、`msg`、`total`、`rows`。
- 失败时返回统一错误结构，`msg` 为失败原因。

### 2.1 枚举说明

| 枚举 | 值 | 说明 |
|---|---|---|
| isBroadcast | `1` | 全员广播消息（`receiver_id IS NULL`） |
| isBroadcast | `0` | 定向消息（`receiver_id IS NOT NULL`） |
| isRead | `0` | 未读 |
| isRead | `1` | 已读 |
| linkType | `1` | 帖子详情 |
| linkType | `2` | 活动页 |
| linkType | `3` | 外链 |
| linkType | `4` | 无跳转 |

### 2.2 发送规则说明

- 不传 `memberIds`：按全员广播发送，写入 1 条 `receiver_id=NULL` 的消息。
- 传 `memberIds`：按定向发送，最多 100 个，自动去重后再发送。
- 无效接收者定义：会员不存在或已软删。
- 当传了 `memberIds` 但过滤后无有效会员时：返回成功，`sentCount=0`，不会降级成广播。

## 3. 详细接口说明

### 3.1 官方发送者下拉选项

- 方法：`GET`
- 路径：`/api/admin/message/system/senderOptions`
- 说明：返回可用于发送系统消息的官方正常账号。

#### 响应示例（成功）

```json
{
    "code": 200,
    "msg": "查询成功",
    "data": [
        {
            "memberId": 10001,
            "nickname": "官方助手",
            "avatar": "https://cdn.example.com/app/avatar/official-10001.png",
            "officialLabel": "官方"
        }
    ]
}
```

#### `data` 字段说明

| 字段 | 类型 | 说明 |
|---|---|---|
| memberId | number | 官方会员ID（发送接口 `senderId`） |
| nickname | string | 昵称 |
| avatar | string | 头像 URL |
| officialLabel | string | 官方标签 |

---

### 3.2 发送系统消息

- 方法：`POST`
- 路径：`/api/admin/message/system/send`
- 说明：支持广播发送与定向发送。

#### Body 参数（JSON）

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| senderId | number | 是 | 发送者会员ID，必须是官方正常账号 |
| title | string | 是 | 消息标题，最大 100 字符 |
| content | string | 是 | 消息内容 |
| coverUrl | string | 否 | 封面图 URL，最大 500 字符 |
| linkType | number | 否 | 跳转类型：`1/2/3/4` |
| linkUrl | string | 否 | 跳转链接/目标ID，最大 500 字符 |
| memberIds | number[] | 否 | 定向接收会员ID数组，最多 100 个；不传表示广播 |

#### 请求示例（广播发送）

```json
{
    "senderId": 10001,
    "title": "平台通知",
    "content": "社区活动将于今晚20:00开始",
    "linkType": 4
}
```

#### 请求示例（定向发送）

```json
{
    "senderId": 10001,
    "title": "学习提醒",
    "content": "您报名的课程将在明天开课，请及时查看课表",
    "linkType": 2,
    "linkUrl": "/app/course/schedule",
    "memberIds": [10010, 10011, 10012]
}
```

#### 响应示例（成功）

```json
{
    "code": 200,
    "msg": "发送成功",
    "data": {
        "sentCount": 3
    }
}
```

#### 失败场景

- 参数错误：返回首个参数校验错误文案。
- 发送者非法（非官方、禁用或已软删）：返回 `发送者必须是官方正常账号`。
- 其他异常：返回 `操作失败，请稍后重试`。

---

### 3.3 系统消息分页列表

- 方法：`GET`
- 路径：`/api/admin/message/system/list`
- 说明：默认展示广播消息，支持多维筛选。

#### Query 参数

| 参数 | 类型 | 必填 | 默认值 | 说明 |
|---|---|---|---|---|
| pageNum | number | 否 | 1 | 页码，最小 1 |
| pageSize | number | 否 | 10 | 每页条数，`1~100` |
| isBroadcast | number | 否 | 1 | 是否全员广播：`1=广播`、`0=定向` |
| memberId | number | 否 | - | 接收者会员ID（严格匹配 `receiver_id`） |
| beginTime | string | 否 | - | 发送开始时间，格式可被 `date` 识别 |
| endTime | string | 否 | - | 发送结束时间，格式可被 `date` 识别，且不能早于 beginTime |
| isRead | number | 否 | - | 已读筛选：`0=未读`、`1=已读` |

#### 筛选语义（严格字段过滤）

- `isBroadcast=1` 时追加 `receiver_id IS NULL` 条件。
- `isBroadcast=0` 时追加 `receiver_id IS NOT NULL` 条件。
- 传 `memberId` 时始终追加 `receiver_id = memberId` 条件。
- `isBroadcast=1 & isRead=1` 允许返回空结果。

#### 请求示例

```http
GET /api/admin/message/system/list?pageNum=1&pageSize=10&isBroadcast=0&memberId=10010&beginTime=2026-03-01 00:00:00&endTime=2026-03-31 23:59:59&isRead=0
```

#### 响应示例（成功）

```json
{
    "code": 200,
    "msg": "查询成功",
    "total": 1,
    "rows": [
        {
            "messageId": 5012,
            "sender": {
                "memberId": 10001,
                "nickname": "官方助手",
                "avatar": "https://cdn.example.com/app/avatar/official-10001.png",
                "officialLabel": "官方"
            },
            "receiver": {
                "memberId": 10010,
                "nickname": "张三",
                "avatar": "https://cdn.example.com/app/avatar/member-10010.png"
            },
            "isBroadcast": 0,
            "title": "学习提醒",
            "content": "您报名的课程将在明天开课，请及时查看课表",
            "coverUrl": "",
            "linkType": 2,
            "linkTypeName": "活动页",
            "linkUrl": "/app/course/schedule",
            "isRead": 0,
            "createdAt": "2026-03-16 18:20:10"
        }
    ]
}
```

#### `rows` 字段说明

| 字段 | 类型 | 说明 |
|---|---|---|
| messageId | number | 消息ID |
| sender | object | 发送者信息（memberId/nickname/avatar/officialLabel） |
| receiver | object | 接收者信息（memberId/nickname/avatar） |
| isBroadcast | number | 是否广播：`1=广播`、`0=定向` |
| title | string | 消息标题 |
| content | string | 消息内容 |
| coverUrl | string | 封面图 URL |
| linkType | number/null | 跳转类型 |
| linkTypeName | string/null | 跳转类型名称 |
| linkUrl | string | 跳转链接/目标ID |
| isRead | number | 已读状态：`0/1` |
| createdAt | string/null | 发送时间，格式 `Y-m-d H:i:s` |

