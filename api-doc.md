## 1. 瀑布流列表接口

### 请求信息

- **接口地址**: `/api/v1/post/list`
- **请求方式**: `GET`
- **Content-Type**: `application/json`

### 请求参数

| 字段名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| page | number | 是 | 页码，从 1 开始 |
| pageSize | number | 是 | 每页数量，默认 10 |

### 响应参数

| 字段名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| code | number | 是 | 状态码，200 表示成功 |
| message | string | 是 | 响应信息 |
| data | array | 是 | 帖子列表，空数组表示没有更多数据 |

#### data 数组元素

| 字段名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| id | number | 是 | 帖子 ID |
| cover | string | 是 | 封面图片链接 |
| title | string | 否 | 标题，可为空字符串 |
| avatar | string | 是 | 作者头像链接 |
| nickname | string | 是 | 作者昵称 |
| likes | number | 是 | 点赞数 |
| isVideo | boolean | 否 | 是否为视频，默认 false |
| aspectRatio | number | 是 | 封面图宽高比（宽/高），用于瀑布流布局计算 |

### 响应示例

```json
{
  "code": 200,
  "message": "success",
  "data": [
    {
      "id": 1,
      "cover": "https://example.com/cover1.jpg",
      "title": "八大器官有寒，寒在哪儿？",
      "avatar": "https://example.com/avatar1.jpg",
      "nickname": "云舒",
      "likes": 128,
      "isVideo": false,
      "aspectRatio": 0.75
    },
    {
      "id": 2,
      "cover": "https://example.com/cover2.jpg",
      "title": "",
      "avatar": "https://example.com/avatar2.jpg",
      "nickname": "张锋",
      "likes": 256,
      "isVideo": true,
      "aspectRatio": 1.2
    }
  ]
}
```

### 分页说明

- 当返回的 `data` 为空数组时，表示已加载完所有数据
- 前端根据 `aspectRatio` 计算图片显示高度：`图片高度 = 容器宽度 / aspectRatio`

---

## 错误码说明

| 错误码 | 说明 |
|--------|------|
| 200 | 成功 |
| 400 | 请求参数错误 |
| 401 | 未授权 |
| 404 | 接口不存在 |
| 500 | 服务器内部错误 |
