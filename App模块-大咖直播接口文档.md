# 课程页「大咖直播」接口文档

## 1. 接口说明

- **接口地址**：`/api/app/v1/course/live`
- **请求方式**：`GET`
- **接口用途**：App模块-「大咖直播」模块数据
- **设计目标**：同一列表内同时支持以下三类卡片状态
  - `live`：进入直播
  - `upcoming`：预约 / 已预约
  - `replay`：看回放

> 说明：
> - 本接口仅返回课程页模块展示所需的摘要数据。
> - 点击“进入直播”时，前端仍调用 `/api/app/v1/live/enter` 获取实时入会参数。
> - 点击“预约”时，前端仍调用 `/api/app/v1/live/reserve`。

## 后端业务逻辑说明
1. 这个接口需要获取三种不同直播状态的数据，分别是：直播中、直播预告和直播回放
2. 这三种不同的数据可以设置统一的limit，比如limit=2，那么就是2*3=6条数据
3. 直播中和直播预告需要从app_live_room数据表中获取数据，固定仅查询 `is_show_index=1` 的直播间，获取距离当前时间最近的limit条数据，返回响应的时候，data.id 是 room_id
4. 直播回放数据需要从app_live_playback数据表中获取数据，且仅关联 `is_show_index=1` 的直播间，获取距离当前时间最近的limit条数据，返回响应的时候，data.id 是 third_party_room_id

---

## 2. 请求参数

当前课程页使用场景下，建议本接口**无需请求参数**。

### 请求示例

```http
GET /api/app/v1/course/live
```



---

## 3. 响应数据

### 响应结构

```json
{
  "code": 200,
  "message": "success",
  "data": [
    {
      "id": 60001,
      "title": "春季养生直播：睡眠调理与体质改善",
      "cover": "https://example.com/live-cover.jpg",
      "startTime": "2026-03-15 19:30:00",
      "status": "live",
      "reserveCount": 126,
      "isReserved": false,
      "watchCount": 582,
      "actionText": "进入直播"
    },
    {
      "id": 60002,
      "title": "节气养生公开课：春分前后如何调饮食",
      "cover": "https://example.com/live-cover-2.jpg",
      "startTime": "2026-03-18 20:00:00",
      "status": "upcoming",
      "reserveCount": 88,
      "isReserved": true,
      "watchCount": 0,
      "actionText": "已预约"
    },
    {
      "id": 60003,
      "title": "中医基础入门直播回放",
      "cover": "https://example.com/live-cover-3.jpg",
      "startTime": "2026-03-10 19:30:00",
      "status": "replay",
      "reserveCount": 0,
      "isReserved": false,
      "watchCount": 1260,
      "liveToken": "mock-playback-token-60003",
      "actionText": "看回放"
    }
  ]
}
```

---

## 4. 字段说明

### 顶层字段

| 字段名 | 类型 | 说明 |
| --- | --- | --- |
| code | number | 业务状态码，成功建议返回 `200` 或 `0` |
| message | string | 响应消息，如 `success` |
| data | array | 课程页「大咖直播」模块卡片列表 |

### data 列表项字段

| 字段名 | 类型 | 必填 | 说明 |
| --- | --- | --- |  |
| id | number / string | 是 | 直播中和直播预告的时候id字段代表 app_live_room 数据表中的room_id（内部直播间ID）；回放的时候 id 字段代表app_live_playback数据表的third_party_room_id（百家云直播ID） |
| title | string | 是 | 直播标题 |
| cover | string | 是 | 直播封面图 |
| startTime | string | 是 | 开播时间，格式建议 `YYYY-MM-DD HH:mm:ss` |
| status | string | 是 | 直播状态：`live` / `upcoming` / `replay` / `ended` |
| reserveCount | number | 否 | 预约人数，`upcoming` 场景优先使用 |
| isReserved | boolean | 否 | 当前用户是否已预约，`upcoming` 场景使用 |
| watchCount | number | 否 | 观看次数，`live` / `replay` 场景优先使用 |
| liveToken | string | 否 | 回放令牌，`replay` 场景必填，前端使用 `playback.startPlayback(id, liveToken, {})` 播放 |
| actionText | string | 否 | 按钮文案，可选；若不传，前端按 `status` 和 `isReserved` 自动计算 |

---

## 5. 状态与前端行为约定

| status | 前端按钮 | 统计文案 | 说明 |
| --- | --- | --- | --- |
| live | 进入直播 | `${watchCount}次观看` | 点击按钮时调用 `/api/app/v1/live/enter` |
| upcoming | 预约 / 已预约 | `${reserveCount}人预约` | 点击按钮时调用 `/api/app/v1/live/reserve` |
| replay | 看回放 | `${watchCount}次观看` | 点击按钮时使用 `id + liveToken` 播放回放 |
| ended | 已结束 / 不展示 | 可选 | 如课程页不需要，可不返回该状态 |

### `actionText` 前端兜底规则

当前端未收到 `actionText` 时，默认按以下规则计算：

- `status = live` -> `进入直播`
- `status = upcoming` 且 `isReserved = false` -> `预约`
- `status = upcoming` 且 `isReserved = true` -> `已预约`
- `status = replay` -> `看回放`

---

## 6. 字段精简建议

本次接口升级后，建议删除以下旧字段，避免前后端长期双字段并存：

| 旧字段 | 是否删除 | 原因 |
| --- | --- | --- |
| liveTime | 建议删除 | 统一改用 `startTime` |
| viewCount | 建议删除 | 统一改用 `watchCount` |
| replayUrl | 建议删除 | 当前项目真实回放播放依赖 `liveToken`，不依赖视频地址 |

### 不建议放在本接口里的字段

以下字段建议不要放到 `/api/app/v1/course/live`：

| 字段名 | 原因 |
| --- | --- |
| code | 进入直播的实时参数，应在点击时通过 `/api/app/v1/live/enter` 获取 |
| sign | 同上，属于实时入会参数 |
| roomId | 同上，属于实时入会参数 |
| teacherDesc / detailUrl 等详情字段 | 课程页直播模块只做摘要展示，不承担详情页职责 |

---

## 7. 排序建议

建议后端直接按以下优先级返回列表，前端无需再额外排序：

1. `live`
2. `upcoming`
3. `replay`

同状态下建议按时间排序：

- `live` / `upcoming`：按 `startTime` 近到远
- `replay`：按 `startTime` 近到远或最近回放优先

---

## 8. 错误响应示例

```json
{
  "code": 500,
  "message": "获取课程直播列表失败",
  "data": []
}
```

---

## 9. 联调结论

课程页最终只依赖这一组字段：

- `id`
- `title`
- `cover`
- `startTime`
- `status`
- `reserveCount`
- `isReserved`
- `watchCount`
- `liveToken`
- `actionText`（可选）

如果后端按以上契约返回，前端即可支持：

- 进入直播
- 预约 / 已预约
- 看回放
