# 录播课章节管理 API

## 基础路径

`/course/video/chapter`

---

## 1. 章节列表（分页）

**GET** `/list/{courseId}`

### 请求参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| courseId | int | 是 | 课程ID（路径参数） |
| pageNum | int | 否 | 页码，默认1 |
| pageSize | int | 否 | 每页条数，默认10 |

### 响应示例

```json
{
    "code": 200,
    "msg": "查询成功",
    "total": 20,
    "rows": [
        {
            "chapterId": 1,
            "courseId": 10,
            "chapterTitle": "第一章 课程介绍",
            "isFreeTrial": 1,
            "status": 1,
            "sortOrder": 1,
            "unlockTime": "2025-06-01 00:00:00",
            "createTime": "2025-05-20 10:00:00"
        }
    ]
}
```

---

## 2. 章节列表（全部，用于排序）

**GET** `/all/{courseId}`

### 请求参数

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| courseId | int | 是 | 课程ID（路径参数） |

### 响应示例

```json
{
    "code": 200,
    "msg": "操作成功",
    "data": [
        {
            "chapterId": 1,
            "courseId": 10,
            "chapterTitle": "第一章 课程介绍",
            "isFreeTrial": 1,
            "status": 1,
            "sortOrder": 1,
            "unlockTime": null,
            "createTime": "2025-05-20 10:00:00"
        }
    ]
}
```
