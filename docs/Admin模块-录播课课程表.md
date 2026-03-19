# Admin 模块录播课课程表接口文档

## 1. 接口列表总览

| 功能     | 方法  | 路径                                              |
|--------|-----|-------------------------------------------------|
| 录播课课程表 | GET | `/api/admin/course/videoCourseSheet/{courseId}` |

## 2. 通用说明

- 鉴权：所有接口都需要 `Authorization: Bearer {token}`
- 请求头建议：

```http
Content-Type: application/json
Accept: application/json
Authorization: Bearer {token}
```

### 2.1 通用响应示例

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
    "code": 1201,
    "msg": "操作失败",
    "data": []
}
```

## 3. 详细接口说明

### 3.1 获取录播课课程表

- 方法：`GET`
- 路径：`/api/admin/course/videoCourseSheet/{courseId}`

#### Path 参数

| 参数       | 类型     | 必填 | 说明         |
|----------|--------|----|------------|
| courseId | number | 是  | 课程 ID（正整数） |

#### 响应示例 JSON（成功）

```json
{
    "code": 200,
    "msg": "查询成功",
    "data": [
        {
            "chapterId": 1001,
            "courseId": 2001,
            "chapterTitle": "第一章：课程导学",
            "chapterSubtitle": "明确学习目标与学习路径",
            "coverImage": "https://cdn.example.com/course/chapter-cover-1001.jpg",
            "videoId": 7100,
            "videoUrl": "https://cdn.example.com/video/lesson-7100.mp4",
            "videoDuration": 3661,
            "videoDurationText": "01:01:01",
            "videoTitle": "第一章导学视频",
            "videoCoverImage": "https://cdn.example.com/video/cover-7100.jpg",
            "videoWidth": 1920,
            "videoHeight": 1080,
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

#### 响应示例 JSON（失败）

```json
{
    "code": 1201,
    "msg": "操作失败",
    "data": []
}
```

#### `data` 字段说明

| 字段                | 类型           | 说明                                     |
|-------------------|--------------|----------------------------------------|
| chapterId         | number       | 章节 ID                                  |
| courseId          | number       | 课程 ID                                  |
| chapterTitle      | string       | 章节标题                                   |
| chapterSubtitle   | string       | 章节副标题                                  |
| coverImage        | string\|null | 章节封面地址                                 |
| videoId           | number\|null | 章节绑定的视频 ID（单选）                         |
| videoUrl          | string\|null | 章节视频播放地址                               |
| videoDuration     | number       | 章节视频时长（秒）；无视频内容时返回 `0`                 |
| videoDurationText | string       | 章节视频时长文本；无视频内容时返回 `"00:00"`            |
| videoTitle        | string\|null | 视频标题（优先系统视频元数据）                        |
| videoCoverImage   | string\|null | 视频封面（优先系统视频元数据）                        |
| videoWidth        | number       | 视频宽度                                   |
| videoHeight       | number       | 视频高度                                   |
| isFree            | number       | 是否免费：`0` 否，`1` 是                       |
| unlockType        | number       | 解锁类型：`1` 立即解锁，`2` 按天数解锁，`3` 按日期解锁      |
| unlockDays        | number       | 解锁天数（仅 `unlockType=2` 有业务意义）           |
| unlockDate        | string\|null | 固定解锁日期（`Y-m-d`，仅 `unlockType=3` 有业务意义） |
| chapterStartTime  | string\|null | 章节开始时间（`Y-m-d H:i:s`）                  |
| chapterEndTime    | string\|null | 章节结束时间（`Y-m-d H:i:s`）                  |
| status            | number       | 章节状态：`0` 草稿，`1` 上架，`2` 下架              |
| sortOrder         | number       | 排序值（升序）                                |
| createTime        | string\|null | 创建时间（`Y-m-d H:i:s`）                    |

### 3.2 字段与规则补充

- `videoDurationText` 格式规则：
    - 时长 `< 1` 小时时输出 `MM:SS`；
    - 时长 `>= 1` 小时时输出 `HH:MM:SS`。
- 当章节无 `videoContent` 时，默认返回：
    - `videoUrl = null`
    - `videoDuration = 0`
    - `videoDurationText = "00:00"`

