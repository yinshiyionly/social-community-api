## 新的App模块直播首页接口文档

---

## 1. 获取直播首页数据（单接口 + tab 参数区分）

### 请求信息

- **接口地址**：`/api/app/v1/live/home`
- **请求方式**：GET
- **接口说明**：用于直播页面，按 `tab` 返回「直播预告 / 直播回放」列表，并返回顶部 `latest` 卡片数据。

### 后端业务说明
- 直播预告的数据需要从 app_live_room 数据表查询数据，且固定仅查询 `is_show_index=1` 的直播间；直播预告还需要关联 app_live_room_stat数据表获取该直播间的预约人数，再关联app_live_room_reserve数据表判断用户对于该直播的预约状态
- 直播回放数据需要从app_live_playback数据表查询数据并组建
- latest 卡片数据需要在app_live_room数据表中查询距离今天最近的正在直播或者即将开播的直播数据，只需要一条数据，正在直播的数据优先级比即将直播的优先级要高，同样仅查询 `is_show_index=1` 数据

### 请求参数（Query）

| 参数名 | 类型 | 必填 | 说明 |
| --- | --- | --- | --- |
| tab | string | 是 | 列表类型：`upcoming`（直播预告）/ `replay`（直播回放） |
| page | number | 否 | 页码，默认 `1` |
| pageSize | number | 否 | 每页条数，默认 `20` |

### 响应数据

```json
{
  "code": 200,
  "message": "success",
  "data": {
    "latest": {
      "id": 60001,
      "title": "诗直播 不超过10个字符",
      "cover": "/static/images/avatar.jpg",
      "startTime": "2026-01-06 14:00:00",
      "status": "upcoming",
      "reserveCount": 37,
      "isReserved": false,
      "actionText": "预约"
    },
    "tab": "upcoming",
    "list": [
      {
        "id": 60002,
        "title": "1月7日：小寒养生3步走：练好内、吃好、睡得好",
        "cover": "/static/images/avatar.jpg",
        "startTime": "2026-01-07 19:00:00",
        "status": "upcoming",
        "reserveCount": 178,
        "isReserved": false,
        "actionText": "预约"
      }
    ],
    "total": 5,
    "page": 1,
    "pageSize": 20,
    "hasMore": false
  }
}
```

### 字段说明

`latest` 与 `list` 单条字段：

| 字段名 | 类型 | 说明 |
| --- | --- | --- |
| id | number/string | 直播ID |
| title | string | 直播标题 |
| cover | string | 封面图地址 |
| startTime | string | 开播时间 |
| status | string | 状态：`upcoming` / `live` / `replay` / `ended` |
| reserveCount | number | 预约人数（预告场景） |
| isReserved | boolean | 当前用户是否已预约（预告场景） |
| watchCount | number | 观看人数（回放场景） |
| durationSec | number | 回放时长（秒） |
| replayUrl | string | 回放视频地址（回放场景） |
| actionText | string | 按钮文案，如 `预约` / `已预约` / `回放` |

---
