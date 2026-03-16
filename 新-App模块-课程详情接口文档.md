# 课程详情（章节判断 + 详情接口）接口文档

> 本文档供后端联调用，仅描述接口契约与字段规则。

## 0. 接口清单

1. 判断是否有章节：`GET /api/app/v1/course/has-chapters`
2. 无章节详情（旧版）：`GET /api/app/v1/course/detail-legacy`
3. 有章节详情（章节版）：`GET /api/app/v1/course/detail-chapters`

统一响应结构：`code + msg/message + data`；成功码只认 `0` 或 `200`。

---

## 1. 判断是否有章节

- **接口地址**：`/api/app/v1/course/has-chapters`
- **请求方式**：`GET`

### 1.1 请求参数

| 参数名 | 类型 | 必填 | 说明 |
| --- | --- | --- | --- |
| id | number | 是 | 课程 ID |

### 1.2 响应数据

`data` 直接返回 boolean：

- `false`：后续应调用 **无章节详情**接口 `detail-legacy`
- `true`：后续应调用 **有章节详情**接口 `detail-chapters`

响应示例：

```json
{
  "code": 200,
  "message": "success",
  "data": true
}
```

---

## 2. 无章节详情（旧版）

- **接口地址**：`/api/app/v1/course/detail-legacy`
- **请求方式**：`GET`
- **接口用途**：无章节课程详情页数据（核心为一张长图 `contentImage`）

### 2.1 请求参数

| 参数名 | 类型 | 必填 | 说明 |
| --- | --- | --- | --- |
| id | number | 是 | 课程 ID |

### 2.2 响应数据

响应示例：

```json
{
  "code": 200,
  "message": "success",
  "data": {
    "contentImage": "https://example.com/content.jpg",
    "limitPrice": "9.00",
    "originalPrice": "199.00",
    "discountPoints": "10",
    "buttonText": "立即购买",
    "buttonActionType": "buy"
  }
}
```

字段说明：

| 字段名 | 类型 | 必填 | 说明 |
| --- | --- | --- | --- |
| contentImage | string | 是 | 详情内容区域图片（整张长图） |
| limitPrice | string | 否 | 价格（元）；免费课可不返回或返回 `"0"` |
| originalPrice | string | 否 | 原价（元），有则展示 |
| discountPoints | string | 否 | 优惠积分，有则展示 |
| buttonText | string | 是 | 按钮文案：`立即购买` / `免费领取课程` 等 |
| buttonActionType | string | 否 | 按钮行为类型：例如 `buy` / `free_receive` |

---

## 3. 有章节详情（章节版）

- **接口地址**：`/api/app/v1/course/detail-chapters`
- **请求方式**：`GET`
- **接口用途**：有章节课程详情页数据（封面/标题/讲师/学习次数 + 章节列表 + 底部购买）

### 3.1 请求参数

| 参数名 | 类型 | 必填 | 说明 |
| --- | --- | --- | --- |
| id | number | 是 | 课程 ID |

### 3.2 响应数据

响应示例：

```json
{
  "code": 200,
  "message": "success",
  "data": {
    "title": "课程标题课程标题",
    "coverImage": "https://example.com/cover.jpg",
    "teacherName": "萌萌老师",
    "studyCountText": "5.70次",
    "intro": "这里是课程简介...",

    "isUnlocked": false,
    "chapters": [
      {
        "id": 1,
        "title": "章节标题",
        "dateText": "2025年08月01日",
        "durationText": "18分钟",
        "isFree": true
      },
      {
        "id": 2,
        "title": "章节标题",
        "dateText": "2025年08月01日",
        "durationText": "18分钟",
        "isFree": false
      }
    ],

    "limitPrice": "9.00",
    "originalPrice": "199.00",
    "discountPoints": "10",
    "buttonText": "立即购买",
    "buttonActionType": "buy"
  }
}
```

字段说明：

| 字段名 | 类型 | 必填 | 说明 |
| --- | --- | --- | --- |
| title | string | 是 | 课程标题 |
| coverImage | string | 是 | 封面图 |
| teacherName | string | 是 | 主讲人 |
| studyCountText | string | 是 | 学习次数/学习人数展示文本（前端直接展示） |
| intro | string | 否 | 简介内容（纯文本） |
| isUnlocked | boolean | 是 | 是否已解锁整门课程（购买/领取后为 true；true 时所有章节可播放） |
| chapters | array | 是 | 章节列表 |
| limitPrice | string | 否 | 价格（元）；免费课可不返回或返回 `"0"` |
| originalPrice | string | 否 | 原价（元），有则展示 |
| discountPoints | string | 否 | 优惠积分，有则展示 |
| buttonText | string | 是 | 按钮文案：`立即购买` / `免费领取课程` 等 |
| buttonActionType | string | 否 | 按钮行为类型：例如 `buy` / `free_receive` |

### 3.3 chapters 列表项

| 字段名 | 类型 | 必填 | 说明 |
| --- | --- | --- | --- |
| id | number/string | 是 | 章节 ID |
| title | string | 是 | 章节标题 |
| dateText | string | 否 | 日期展示文本，例如 `2025年08月01日` |
| durationText | string | 否 | 时长展示文本，例如 `18分钟` |
| isFree | boolean | 是 | 是否免费章节（未解锁整课时，免费章节可播放） |

---

## 4. 强约束规则（后端必须保证）

- 当 `has-chapters=false` 时：`detail-legacy` 必须返回 `contentImage`。
- 当 `has-chapters=true` 时：`detail-chapters` 必须返回 `title/coverImage/teacherName/studyCountText/isUnlocked/chapters`。

## 5. 前端播放/锁展示规则（供后端核对数据）

- 播放按钮：`isUnlocked = true` **或** `chapter.isFree = true`
- 锁图标：`isUnlocked = false` **且** `chapter.isFree = false`

