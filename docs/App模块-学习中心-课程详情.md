# App 学习中心课程详情接口文档

## 1. 接口概览

- 接口名称：学习中心课程详情
- 请求方式：`GET`
- 接口路径：`/api/app/v1/study/course/detail`
- 接口用途：用于学习中心课程详情页，返回课程头部信息、每日计划 tabs、当前选中计划的章节/作业列表。

---

## 2. 鉴权与请求头

- 鉴权方式：`app.auth`（必需登录）
- 请求头建议：

```http
Accept: application/json
Authorization: Bearer {token}
```

---

## 3. Query 参数

| 参数 | 类型 | 必填 | 说明                                   |
| --- | --- | --- |--------------------------------------|
| courseId | number | 是 | 课程 ID，正整数                            |
| planKey | string | 否 | 计划 key，使用 tabs.key,格式是 `YYYY-MM-DD` |

### 请求示例

```http
GET /api/app/v1/study/course/detail?courseId=1001&planKey=2026-03-17
Authorization: Bearer {token}
```

---

## 4. 成功响应示例（录播课，章节有视频地址）

```json
{
  "code": 200,
  "msg": "success",
  "data": {
    "course": {
      "courseId": 1001,
      "courseTitle": "5天手机短视频拍剪小白营",
      "lecturerName": "李扬老师",
      "classTeacherName": "太极-绵绵老师",
      "classTeacherQr": "https://dev-hobby-app.tos-cn-beijing.volces.com/admin/image/20260313/qr.png"
    },
    "dailyPlan": {
      "selectedPlanKey": "2026-03-17",
      "todayPlanKey": "2026-03-17",
      "tabs": [
        {
          "key": "2026-03-17",
          "type": "day",
          "label": "第1天",
          "date": "03.17",
          "dayNo": 1
        }
      ],
      "items": [
        {
          "itemType": "chapter",
          "chapterId": 6,
          "chapterTitle": "第一章节",
          "scheduleTime": "19:30",
          "coverImage": "https://dev-hobby-app.tos-cn-beijing.volces.com/admin/image/20260313/cacd21a1-9349-4ea7-b57a-ed975979d547.png",
          "videoUrl": "https://dev-hobby-app.tos-cn-beijing.volces.com/video/lesson-6.mp4"
        }
      ]
    }
  }
}
```

---

## 5. 成功响应示例（非录播课或无视频地址）


---

## 6. `data` 字段说明

### 6.1 `data.course`

| 字段 | 类型 | 说明 |
| --- | --- | --- |
| courseId | number | 课程 ID |
| courseTitle | string | 课程标题 |
| lecturerName | string | 主讲老师名称 |
| classTeacherName | string | 班主任名称 |
| classTeacherQr | string | 班主任二维码地址 |

### 6.2 `data.dailyPlan`

| 字段 | 类型 | 说明                                  |
| --- | --- |-------------------------------------|
| selectedPlanKey | string | 当前选中的计划 key 日期字符串或空字符串）             |
| todayPlanKey | string | 若课表中存在今天日期则返回 `YYYY-MM-DD`，否则返回空字符串 |
| tabs | array | 计划 tabs 列表                          |
| items | array | 当前选中 tab 对应的学习项列表                   |

### 6.3 `tabs` 单项

| 字段 | 类型 | 说明                     |
| --- | --- |------------------------|
| key | string | tab 唯一标识， `YYYY-MM-DD` |
| type | string | `day`                  |
| label | string | 展示文案，例如 `第N天`          |
| date | string\|null | 日期 tab 返回 `MM.DD`      |
| dayNo | number\|null | 日期 tab 返回天数序号          |

### 6.4 `items` 单项（联合类型）

#### 章节项（`itemType=chapter`）

| 字段 | 类型 | 说明 |
| --- | --- | --- |
| itemType | string | 固定 `chapter` |
| chapterId | number | 章节 ID |
| chapterTitle | string | 章节标题 |
| scheduleTime | string | 计划时间，格式 `HH:mm` |
| coverImage | string | 封面图地址 |
| videoUrl | string | 章节视频地址；仅录播课可能有值，其余场景为空字符串 |

---

## 7. 业务规则说明

### 7.1 访问与判定

1. 必须登录访问；
2. 课程不存在（或已删除）返回 404；
3. 课程存在但当前用户未拥有返回 403；
4. 仅返回当前选中 `planKey` 对应的 `items`，不是全量计划项。


## 8. 常见失败响应示例

### 8.1 401 未登录

```json
{
  "code": 401,
  "msg": "请先登录"
}
```

### 8.2 403 未拥有课程

```json
{
  "code": 403,
  "msg": "您未拥有该课程"
}
```

### 8.3 404 课程不存在

```json
{
  "code": 404,
  "msg": "课程不存在"
}
```

### 8.4 参数校验失败（`courseId/planKey`）

```json
{
  "code": 400,
  "msg": "planKey格式错误",
  "data": []
}
```

> 说明：参数校验失败响应体 `code=400`，HTTP 状态码由全局异常处理统一返回。

### 8.5 500 服务异常

```json
{
  "code": 500,
  "msg": "服务器繁忙，请稍后重试"
}
```
