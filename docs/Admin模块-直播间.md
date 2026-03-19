# Admin 模块直播间接口文档


## 1. 接口列表总览

| 功能             | 方法     | 路径                                   |
|----------------|--------|--------------------------------------|
| 获取课程常量选项        | GET    | `/api/admin/live/room/constants`          |
| 直播间分页列表        | GET    | `/api/admin/live/room/list`          |
| 新增直播间          | POST   | `/api/admin/live/room`               |
| 伪直播点播视频分页列表    | GET    | `/api/admin/live/room/mockVideoList` |
| 伪直播回放分页列表      | GET    | `/api/admin/live/room/mockPlaybackList` |
| 删除直播间（不支持批量）   | DELETE | `/api/admin/live/room/{roomId}`      |

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

## 3. 详细接口说明

### 3.1 获取课程常量选项

- 方法：`GET`
- 路径：`/api/admin/live/room/constants`
- 参数：无
- 说明：返回课程表单所需的枚举选项（付费类型、播放类型、排课类型、状态），前端可直接用于 Select / Radio 等组件渲染。

#### 响应示例 JSON

```json
{
    "code": 200,
    "msg": "操作成功",
    "data": {
        "liveTypeOptions": [
            {
                "label": "主播模式",
                "value": 1
            },
            {
                "label": "伪直播",
                "value": 2
            }
        ],
        "liveStatusOptions": [
            {
                "label": "未开始",
                "value": 0
            },
            {
                "label": "直播中",
                "value": 1
            },
            {
                "label": "已结束",
                "value": 2
            },
            {
                "label": "已取消",
                "value": 3
            }
        ],
        "mockVideoSourceOptions": [
            {
                "label": "百家云回放",
                "value": 1
            },
            {
                "label": "百家云点播视频",
                "value": 2
            },
            {
                "label": "系统视频文件",
                "value": 3
            }
        ],
        "appTemplateOptions": [
            {
                "label": "横屏",
                "value": 1
            },
            {
                "label": "竖屏",
                "value": 2
            }
        ],
        "enableLiveSellOptions": [
            {
                "label": "禁用带货模版",
                "value": 0
            },
            {
                "label": "视频带货模版",
                "value": 1
            },
            {
                "label": "PPT带货模版",
                "value": 2
            }
        ],
        "isShowIndexOptions": [
            {
                "label": "是-展示在首页",
                "value": 1
            },
            {
                "label": "否-不展示在首页",
                "value": 0
            }
        ]
    }
}
```

#### `data` 字段说明

| 字段                     | 类型    | 说明         |
|------------------------|-------|------------|
| liveTypeOptions        | array | 直播类型选项     |
| liveStatusOptions      | array | 直播状态选项     |
| mockVideoSourceOptions | array | 伪直播素材来源选项  |
| appTemplateOptions     | array | APP端模版样式选项 |
| enableLiveSellOptions  | array | 带货模版样式选项   |
| isShowIndexOptions     | array | 首页展示开关选项   |

每个选项对象结构：

| 字段    | 类型     | 说明   |
|-------|--------|------|
| label | string | 显示文本 |
| value | number | 枚举值  |

### 3.2 获取伪直播点播视频分页列表

- 方法：`GET`
- 路径：`/api/admin/live/room/mockVideoList`
- 说明：
    - 仅用于“创建直播-伪直播-选择点播视频”场景；
    - 固定只返回“转码成功 + 存在 play_url”的可播放视频。
    - 响应结构与后台分页接口保持一致：`code`、`msg`、`total`、`rows`。

#### Query 参数

| 参数       | 类型     | 必填 | 说明                    |
|----------|--------|----|-----------------------|
| pageNum  | number | 否  | 页码，默认 `1`             |
| pageSize | number | 否  | 每页条数，默认 `10`          |
| videoId  | number | 否  | 视频 ID（精确匹配）          |
| name     | string | 否  | 视频名称（模糊匹配）           |

#### 响应示例 JSON

```json
{
    "code": 200,
    "msg": "查询成功",
    "total": 1,
    "rows": [
        {
            "videoId": 313856340,
            "name": "示例点播视频",
            "prefaceUrl": "https://cdn.example.com/video/313856340-cover.jpg",
            "playUrl": "https://cdn.example.com/video/313856340.m3u8",
            "length": 890,
            "lengthText": "00:14:50",
            "status": 100,
            "statusText": "转码成功",
            "publishStatus": 1,
            "publishStatusText": "已发布",
            "uploadTime": "2026-03-10 16:40:57",
            "createdAt": "2026-03-10 16:40:57"
        }
    ]
}
```

#### `rows` 字段说明

| 字段                | 类型           | 说明                           |
|-------------------|--------------|------------------------------|
| videoId           | number       | 百家云视频 ID，创建直播时透传到 `mockVideoId` |
| name              | string       | 视频名称                         |
| prefaceUrl        | string\|null | 视频封面地址                       |
| playUrl           | string       | 视频播放地址                       |
| length            | number       | 时长（秒）                        |
| lengthText        | string       | 时长展示文案（`HH:mm:ss`）          |
| status            | number       | 转码状态（固定返回 `100`）             |
| statusText        | string       | 转码状态文本                       |
| publishStatus     | number       | 发布状态                         |
| publishStatusText | string       | 发布状态文本                       |
| uploadTime        | string\|null | 上传时间（当前与 `createdAt` 一致）     |
| createdAt         | string\|null | 创建时间                         |

### 3.3 获取伪直播回放分页列表

- 方法：`GET`
- 路径：`/api/admin/live/room/mockPlaybackList`
- 说明：
    - 仅用于“创建直播-伪直播-选择回放”场景；
    - 固定只返回“转码成功 + 未屏蔽 + 有播放地址 + 有教室号”的可用回放；
    - 按 `third_party_room_id` 去重，同教室号仅保留最新一条回放记录。

#### Query 参数

| 参数         | 类型     | 必填 | 说明                                 |
|------------|--------|----|------------------------------------|
| pageNum    | number | 否  | 页码，默认 `1`                          |
| pageSize   | number | 否  | 每页条数，默认 `10`                       |
| mockRoomId | string | 否  | 教室号（`third_party_room_id`）精确匹配      |
| name       | string | 否  | 回放名称（模糊匹配）                         |

#### 响应示例 JSON

```json
{
    "code": 200,
    "msg": "查询成功",
    "total": 1,
    "rows": [
        {
            "mockRoomId": "26030953664321",
            "name": "中医公开课回放",
            "prefaceUrl": "https://cdn.example.com/playback/26030953664321-cover.jpg",
            "playUrl": "https://cdn.example.com/playback/26030953664321.m3u8",
            "length": 3600,
            "lengthText": "01:00:00",
            "status": 100,
            "statusText": "转码成功",
            "publishStatus": 1,
            "publishStatusText": "未屏蔽",
            "createTime": "2026-03-10 10:30:00"
        }
    ]
}
```

#### `rows` 字段说明

| 字段              | 类型           | 说明                                  |
|-----------------|--------------|-------------------------------------|
| mockRoomId      | string       | 百家云教室号，创建直播时透传到 `mockRoomId`          |
| name            | string       | 回放名称                                |
| prefaceUrl      | string\|null | 回放封面地址                              |
| playUrl         | string       | 回放播放地址                              |
| length          | number       | 时长（秒）                               |
| lengthText      | string       | 时长展示文案（`HH:mm:ss`）                 |
| status          | number       | 回放状态（固定返回 `100`）                    |
| statusText      | string       | 回放状态文本                              |
| publishStatus   | number       | 屏蔽状态（固定返回 `1`）                      |
| publishStatusText | string     | 屏蔽状态文本                              |
| createTime      | string\|null | 回放生成时间（`create_time`）               |

### 3.4 获取直播间分页列表

- 方法：`GET`
- 路径：`/api/admin/live/room/list`

#### Query 参数

| 参数         | 类型     | 必填 | 说明                                   |
|------------|--------|----|--------------------------------------|
| pageNum    | number | 否  | 页码，默认 `1`                            |
| pageSize   | number | 否  | 每页条数，默认 `10`                         |
| liveType   | number | 否  | 直播类型：`1` 真实直播，`2` 伪直播                |
| liveStatus | number | 否  | 直播状态：`0` 未开始，`1` 直播中，`2` 已结束，`3` 已取消 |
| status     | number | 否  | 启用状态：`0` 禁用，`1` 启用                   |
| roomTitle  | string | 否  | 直播间标题（模糊搜索）                          |
| anchorName | string | 否  | 主播名称（模糊搜索）                           |

#### 响应示例 JSON

```json
{
    "code": 200,
    "msg": "查询成功",
    "total": 1,
    "rows": [
        {
            "roomId": 9,
            "roomTitle": "0310-10-app",
            "roomCover": "https://dev-hobby-app.tos-cn-beijing.volces.com/3545623190/image/20260127/ec5b2490-d529-4e18-abf9-a0821c8b6caa.jpg",
            "liveType": 2,
            "thirdPartyRoomId": 26031082751952,
            "anchorName": "张三",
            "isShowIndex": 1,
            "adminCode": "g5mx5w",
            "teacherCode": "",
            "studentCode": "jua7xm",
            "scheduledStartTime": "2026-03-10 16:40:00",
            "scheduledEndTime": "2026-03-10 22:00:00",
            "enableLiveSell": 1,
            "appTemplate": 2,
            "mockVideoSource": null,
            "mockVideoId": 313856340,
            "mockRoomId": null,
            "liveStatus": 0,
            "liveStatusText": "未开始",
            "status": 1
        }
    ]
}
```

#### `rows` 字段说明

| 字段                 | 类型           | 说明                                    |
|--------------------|--------------|---------------------------------------|
| roomId             | number       | 直播间 ID                                |
| roomTitle          | string       | 直播间标题                                 |
| roomCover          | string\|null | 直播间封面                                 |
| liveType           | number       | 直播类型                                  |
| anchorName         | string\|null | 主播名称                                  |
| isShowIndex        | number       | 是否展示在首页：`1` 展示，`0` 不展示                  |
| adminCode          | string\|null | 助教参加码                                 |
| teacherCode        | string\|null | 老师参加码                                 |
| studentCode        | string\|null | 学生参加码                                 |
| scheduledStartTime | string\|null | 计划开始时间                                |
| scheduledEndTime   | string\|null | 计划结束时间                                |
| enableLiveSell     | number       | 是否使用带货直播模板。0:不使用 1:纯视频带货模板 2:ppt 带货模板 |
| appTemplate        | number       | APP端模板样式，1是横屏，2是竖屏                    |
| mockVideoSource    | number       | 伪直播素材来源: 1=回放 2=百家云视频文件 3=系统视频文件      |
| mockVideoId        | number       | 伪直播关联的点播视频ID                          |
| mockRoomId         | number       | 伪直播关联的回放教室号                           |
| liveStatus         | number       | 直播状态：0=未开始 1=直播中 2=已结束 3=已取消          |
| liveStatusText     | string       | 直播状态文本                                |
| status             | number       | 启用状态（0/1）                             |

---

### 3.5 新增直播间

- 方法：`POST`
- 路径：`/api/admin/live/room`
- 说明：该接口有3个案例：分别是
    - 新增直播-主播模式
    - 新增直播-伪直播-选择回放
    - 新增直播-伪直播-选择点播视频

#### 案例一新增直播-主播模式

##### 请求示例

```json5
{
    "roomTitle": "0310-10-name",
    "liveType": 1,
    // 1-主播模式 2-伪直播 3-24小时直播
    "roomCover": "https://dev-hobby-app.tos-cn-beijing.volces.com/3545623190/image/20260127/ec5b2490-d529-4e18-abf9-a0821c8b6caa.jpg",
    // 直播封面图片地址
    "scheduledStartTime": "2026-03-10 16:40:00",
    // 直播开始时间
    "scheduledEndTime": "2026-03-10 22:00:00",
    // 直播结束时间
    "anchorName": "张三",
    // 主播名称
    "isShowIndex": 1,
    // 是否展示在首页 1=展示 0=不展示
    "enableLiveSell": 1
    // 直播带货模板属性 0：不启用 ，1：是纯视频模板，2：是ppt带货模板 ，请在教室未开始前更新
}
```

#### 案例二新增直播-伪直播-选择回放

```json5
{
    "roomTitle": "0310-10-app",
    "liveType": 2,
    // 1-主播模式 2-伪直播 3-24小时直播
    "roomCover": "https://dev-hobby-app.tos-cn-beijing.volces.com/3545623190/image/20260127/ec5b2490-d529-4e18-abf9-a0821c8b6caa.jpg",
    // 直播封面图片地址
    "scheduledStartTime": "2026-03-10 16:40:00",
    // 直播开始时间
    "scheduledEndTime": "2026-03-10 22:00:00",
    // 直播结束时间
    "mockVideoSource": 1,
    // 伪直播视频来源 1=回放 2=百家云视频文件
    "anchorName": "张三",
    // 主播名称
    "isShowIndex": 1,
    // 是否展示在首页 1=展示 0=不展示
    "enableLiveSell": 1,
    //        直播带货模板属性 0：不启用 ，1：是纯视频模板，2：是ppt带货模板 ，请在教室未开始前更新
    "mockRoomId": 26030953664321
    // 伪直播关联的回放教室号
}
```

#### 案例三新增直播-伪直播-选择点播视频

```json5
{
    "roomTitle": "0310-10-app",
    "liveType": 2,
    // 1-主播模式 2-伪直播 3-24小时直播
    "roomCover": "https://dev-hobby-app.tos-cn-beijing.volces.com/3545623190/image/20260127/ec5b2490-d529-4e18-abf9-a0821c8b6caa.jpg",
    // 直播封面图片地址
    "scheduledStartTime": "2026-03-10 16:40:00",
    // 直播开始时间
    "scheduledEndTime": "2026-03-10 22:00:00",
    // 直播结束时间
    "mockVideoSource": 2,
    // 伪直播视频来源 1=回放 2=百家云视频文件
    "anchorName": "张三",
    // 主播名称
    "isShowIndex": 1,
    // 是否展示在首页 1=展示 0=不展示
    "enableLiveSell": 1,
    //        直播带货模板属性 0：不启用 ，1：是纯视频模板，2：是ppt带货模板 ，请在教室未开始前更新
    "mockVideoId": 313856340
    // 伪直播视频ID
}
```

#### 响应示例 JSON（成功）

```json5
{
    "code": 200,
    "msg": "新增成功",
    "data": {
        "room_id": "26031082737601",
        "student_code": "8796eq",
        "admin_code": "b29cdn",
        "teacher_code": "xwwwxz"
    }
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

### 3.6 删除直播间（不支持批量）

- 方法：`DELETE`
- 路径：`/api/admin/live/room/{roomId}`
- 说明：
    - 仅支持单个删除，不支持批量。
    - 逻辑删除（软删除）。
    - 若直播间已被直播课程章节使用，不允许删除。
    - 若直播间处于直播中，不允许删除。

#### Path 参数

| 参数     | 类型     | 必填 | 说明          |
|--------|--------|----|-------------|
| roomId | number | 是  | 直播间 ID（正整数） |

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
