# 搜索功能 API 对接文档

## 概述

搜索功能支持三种类型的搜索：
- **全部**：返回用户和课程的混合结果
- **用户**：只返回用户搜索结果
- **课程**：只返回课程搜索结果

## 接口列表

### 1. 搜索全部

**接口地址：** `GET /api/search/all`

**请求参数：**

| 参数名 | 类型 | 必填 | 说明 | 示例 |
|--------|------|------|------|------|
| keyword | string | 是 | 搜索关键词 | "养生" |
| page | number | 否 | 页码，默认1 | 1 |
| pageSize | number | 否 | 每页数量，默认10 | 10 |

**响应数据：**

```typescript
{
  "code": 0,
  "message": "success",
  "data": {
    "list": [
      {
        "type": "user",  // 类型：user 或 course
        "data": {
          "id": "user-1",
          "name": "用户名称",
          "avatar": "https://example.com/avatar.jpg",
          "fans": 1000,
          "isFollowed": false
        }
      },
      {
        "type": "course",
        "data": {
          "id": "course-1",
          "title": "课程标题",
          "subtitle": "课程副标题",
          "price": 1.00,
          "originalPrice": 99,
          "cover": "https://example.com/cover.jpg",
          "lessonCount": 10,
          "isLearning": false
        }
      }
    ],
    "total": 100,
    "hasMore": true
  }
}
```

---

### 2. 搜索用户

**接口地址：** `GET /api/search/user`

**请求参数：**

| 参数名 | 类型 | 必填 | 说明 | 示例 |
|--------|------|------|------|------|
| keyword | string | 是 | 搜索关键词 | "张三" |
| page | number | 否 | 页码，默认1 | 1 |
| pageSize | number | 否 | 每页数量，默认10 | 10 |

**响应数据：**

```typescript
{
  "code": 0,
  "message": "success",
  "data": {
    "list": [
      {
        "id": "user-1",
        "name": "用户名称",
        "avatar": "https://example.com/avatar.jpg",
        "fans": 1000,
        "isFollowed": false  // 当前用户是否已关注该用户
      }
    ],
    "total": 50,
    "hasMore": true
  }
}
```

**用户数据字段说明：**

| 字段名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| id | string/number | 是 | 用户ID |
| name | string | 是 | 用户名称 |
| avatar | string | 是 | 用户头像URL |
| fans | number | 是 | 粉丝数量 |
| isFollowed | boolean | 否 | 是否已关注，默认false |

---

### 3. 搜索课程

**接口地址：** `GET /api/search/course`

**请求参数：**

| 参数名 | 类型 | 必填 | 说明 | 示例 |
|--------|------|------|------|------|
| keyword | string | 是 | 搜索关键词 | "养生" |
| page | number | 否 | 页码，默认1 | 1 |
| pageSize | number | 否 | 每页数量，默认10 | 10 |

**响应数据：**

```typescript
{
  "code": 0,
  "message": "success",
  "data": {
    "list": [
      {
        "id": "course-1",
        "title": "小寒养生3步走：练好、吃好、睡得好",
        "subtitle": "副标题描述不超过10个字",
        "price": 1.00,
        "originalPrice": 99,
        "cover": "https://example.com/cover.jpg",
        "lessonCount": 10,
        "isLearning": false  // 当前用户是否正在学习该课程
      }
    ],
    "total": 30,
    "hasMore": true
  }
}
```

**课程数据字段说明：**

| 字段名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| id | string/number | 是 | 课程ID |
| title | string | 是 | 课程标题 |
| subtitle | string | 否 | 课程副标题 |
| price | number | 是 | 课程价格 |
| originalPrice | number | 否 | 课程原价（用于显示划线价格） |
| cover | string | 是 | 课程封面图URL |
| lessonCount | number | 是 | 课程节数 |
| isLearning | boolean | 否 | 是否正在学习，默认false。true时显示"继续学"按钮，false时显示价格 |

---

## 注意事项

1. **响应格式统一：** 所有接口返回格式需要统一，包含 `code`、`message`、`data` 字段
2. **分页支持：** 建议支持分页，返回 `total` 和 `hasMore` 字段
3. **图片URL：** 所有图片字段（avatar、cover）需要返回完整的URL地址
4. **用户状态：** `isFollowed` 和 `isLearning` 字段需要根据当前登录用户返回正确的状态
5. **错误处理：** 接口异常时返回合适的错误码和错误信息
6. **搜索关键词：** 需要对搜索关键词进行 URL 编码处理

---

## 错误码说明

| 错误码 | 说明 |
|--------|------|
| 0 | 成功 |
| 400 | 参数错误 |
| 401 | 未登录 |
| 403 | 无权限 |
| 404 | 资源不存在 |
| 500 | 服务器错误 |

---

## 联系方式

如有疑问，请联系前端开发团队。
