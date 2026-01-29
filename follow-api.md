# 关注页面接口文档

## 概述

关注页面包含以下功能模块：
- 我关注的人列表
- 可能感兴趣的人推荐
- 关注/取消关注操作
- 帖子列表（关注的人的帖子 / 推荐帖子）
- 帖子互动（点赞、收藏）

---

## 1. 获取我关注的人列表

### 请求

```
GET /api/follow/list
```

### 请求参数

无

### 响应

```json
{
  "code": 200,
  "message": "success",
  "data": [
    {
      "id": 1,
      "avatar": "https://xxx.com/avatar.jpg",
      "nickname": "蓝吉儿"
    }
  ]
}
```

### 响应字段说明

| 字段 | 类型 | 说明 |
|------|------|------|
| id | number | 用户ID |
| avatar | string | 头像URL |
| nickname | string | 昵称 |

### 备注

- 未关注任何人时返回空数组 `[]`
- 前端根据返回数组是否为空，决定显示"关注的人"还是"可能感兴趣的人"

---

## 2. 获取可能感兴趣的人

### 请求

```
GET /api/recommend/users
```

### 请求参数

无

### 响应

```json
{
  "code": 200,
  "message": "success",
  "data": [
    {
      "id": 101,
      "avatar": "https://xxx.com/avatar.jpg",
      "nickname": "墨雨云",
      "fansCount": "1.5w粉丝",
      "isFollowed": false
    }
  ]
}
```

### 响应字段说明

| 字段 | 类型 | 说明 |
|------|------|------|
| id | number | 用户ID |
| avatar | string | 头像URL |
| nickname | string | 昵称 |
| fansCount | string | 粉丝数（格式化后的字符串，如 "1.5w粉丝"） |
| isFollowed | boolean | 当前用户是否已关注 |

### 备注

- 固定返回4个推荐用户
- 用于"可能感兴趣的人"模块展示

---

## 3. 关注用户

### 请求

```
POST /api/follow
```

### 请求参数

```json
{
  "userId": 101
}
```

| 字段 | 类型 | 必填 | 说明 |
|------|------|------|------|
| userId | number | 是 | 要关注的用户ID |

### 响应

```json
{
  "code": 200,
  "message": "success",
  "data": null
}
```

---

## 4. 取消关注

### 请求

```
POST /api/unfollow
```

### 请求参数

```json
{
  "userId": 101
}
```

| 字段 | 类型 | 必填 | 说明 |
|------|------|------|------|
| userId | number | 是 | 要取消关注的用户ID |

### 响应

```json
{
  "code": 200,
  "message": "success",
  "data": null
}
```

---

## 5. 获取关注的人的帖子列表

### 请求

```
GET /api/follow/posts
```

### 请求参数

| 字段 | 类型 | 必填 | 默认值 | 说明 |
|------|------|------|--------|------|
| page | number | 否 | 1 | 页码 |
| pageSize | number | 否 | 10 | 每页数量 |

### 响应

```json
{
  "code": 200,
  "message": "success",
  "data": [
    {
      "id": 1001,
      "author": {
        "id": 1,
        "avatar": "https://xxx.com/avatar.jpg",
        "nickname": "蓝吉儿",
        "badge": "优质创作者"
      },
      "content": "今天天气真好，出门散步拍了一些照片，分享给大家～",
      "images": [
        "https://xxx.com/img1.jpg",
        "https://xxx.com/img2.jpg"
      ],
      "commentCount": 56,
      "favoriteCount": 128,
      "likeCount": 320,
      "isFollowed": true,
      "isLiked": false,
      "isFavorited": false,
      "createTime": "2025-01-13T08:30:00.000Z"
    }
  ]
}
```

### 响应字段说明

| 字段 | 类型 | 说明 |
|------|------|------|
| id | number | 帖子ID |
| author | object | 作者信息 |
| author.id | number | 作者用户ID |
| author.avatar | string | 作者头像URL |
| author.nickname | string | 作者昵称 |
| author.badge | string | 作者徽章（可选，如 "官方认证"、"优质创作者"） |
| content | string | 帖子文字内容 |
| images | string[] | 图片URL数组（1-9张） |
| commentCount | number | 评论数 |
| favoriteCount | number | 收藏数 |
| likeCount | number | 点赞数 |
| isFollowed | boolean | 当前用户是否已关注作者 |
| isLiked | boolean | 当前用户是否已点赞 |
| isFavorited | boolean | 当前用户是否已收藏 |
| createTime | string | 发布时间（ISO 8601格式） |

### 备注

- 用于已关注场景，展示关注的人发布的帖子
- 返回空数组表示没有更多数据

---

## 6. 获取推荐帖子列表（猜你喜欢）

### 请求

```
GET /api/recommend/posts
```

### 请求参数

| 字段 | 类型 | 必填 | 默认值 | 说明 |
|------|------|------|--------|------|
| page | number | 否 | 1 | 页码 |
| pageSize | number | 否 | 10 | 每页数量 |

### 响应

与 `/api/follow/posts` 响应结构相同

### 备注

- 用于未关注任何人的场景，展示"猜你喜欢"帖子
- `isFollowed` 字段表示是否已关注该帖子作者

---

## 7. 点赞帖子

### 请求

```
POST /api/post/like
```

### 请求参数

```json
{
  "postId": 1001
}
```

| 字段 | 类型 | 必填 | 说明 |
|------|------|------|------|
| postId | number | 是 | 帖子ID |

### 响应

```json
{
  "code": 200,
  "message": "success",
  "data": null
}
```

---

## 8. 取消点赞

### 请求

```
POST /api/post/unlike
```

### 请求参数

```json
{
  "postId": 1001
}
```

| 字段 | 类型 | 必填 | 说明 |
|------|------|------|------|
| postId | number | 是 | 帖子ID |

### 响应

```json
{
  "code": 200,
  "message": "success",
  "data": null
}
```

---

## 9. 收藏帖子

### 请求

```
POST /api/post/favorite
```

### 请求参数

```json
{
  "postId": 1001
}
```

| 字段 | 类型 | 必填 | 说明 |
|------|------|------|------|
| postId | number | 是 | 帖子ID |

### 响应

```json
{
  "code": 200,
  "message": "success",
  "data": null
}
```

---

## 10. 取消收藏

### 请求

```
POST /api/post/unfavorite
```

### 请求参数

```json
{
  "postId": 1001
}
```

| 字段 | 类型 | 必填 | 说明 |
|------|------|------|------|
| postId | number | 是 | 帖子ID |

### 响应

```json
{
  "code": 200,
  "message": "success",
  "data": null
}
```

---

## 通用响应格式

所有接口统一返回格式：

```json
{
  "code": 200,
  "message": "success",
  "data": {}
}
```

### 错误码说明

| code | 说明 |
|------|------|
| 200 | 成功 |
| 400 | 参数错误 |
| 401 | 未登录 |
| 403 | 无权限 |
| 404 | 资源不存在 |
| 500 | 服务器错误 |

---

## 业务流程说明

### 关注页面加载流程

1. 调用 `/api/follow/list` 获取关注列表
2. 如果返回空数组：
   - 调用 `/api/recommend/users` 获取推荐用户
   - 调用 `/api/recommend/posts` 获取推荐帖子（猜你喜欢）
3. 如果返回有数据：
   - 展示关注的人列表
   - 调用 `/api/follow/posts` 获取关注的人的帖子

### 关注/取消关注流程

1. 点击关注按钮 → 调用 `/api/follow`
2. 点击已关注按钮 → 弹窗确认 → 调用 `/api/unfollow`
3. 前端本地更新 `isFollowed` 状态
