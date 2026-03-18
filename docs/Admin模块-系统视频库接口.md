# Admin 模块系统视频库接口文档

## 1. 接口列表总览

| 功能       | 方法     | 路径                                  |
|----------|--------|-------------------------------------|
| 系统视频常量选项 | GET    | `/api/admin/video/system/constants` |
| 系统视频分页列表 | GET    | `/api/admin/video/system/list`      |
| 系统视频详情   | GET    | `/api/admin/video/system/{videoId}` |
| 新增系统视频   | POST   | `/api/admin/video/system`           |
| 更新系统视频   | PUT    | `/api/admin/video/system`           |
| 删除系统视频   | DELETE | `/api/admin/video/system/{videoId}` |

## 2. 通用说明

- 鉴权：所有接口都需要 `Authorization: Bearer {token}`
- 请求头建议：

```http
Content-Type: application/json
Accept: application/json
Authorization: Bearer {token}
```

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

## 3. 详细接口说明

### 3.1 获取系统视频常量选项

- 方法：`GET`
- 路径：`/api/admin/video/system/constants`
- 参数：无
- 说明：返回系统视频状态和来源选项，用于前端筛选与表单渲染。

#### 响应示例 JSON

```json
{
    "code": 200,
    "msg": "查询成功",
    "data": {
        "statusOptions": [
            {
                "label": "启用",
                "value": 1
            },
            {
                "label": "禁用",
                "value": 2
            }
        ],
        "sourceOptions": [
            {
                "label": "系统",
                "value": "system"
            }
        ]
    }
}
```

#### `data` 字段说明

| 字段            | 类型    | 说明     |
|---------------|-------|--------|
| statusOptions | array | 视频状态选项 |
| sourceOptions | array | 来源选项   |

每个选项对象结构：

| 字段    | 类型             | 说明   |
|-------|----------------|------|
| label | string         | 显示文本 |
| value | number\|string | 枚举值  |

---

### 3.2 获取系统视频分页列表

- 方法：`GET`
- 路径：`/api/admin/video/system/list`

#### Query 参数

| 参数        | 类型     | 必填 | 说明                                |
|-----------|--------|----|-----------------------------------|
| pageNum   | number | 否  | 页码，默认 `1`                         |
| pageSize  | number | 否  | 每页条数，默认 `10`                      |
| videoId   | number | 否  | 视频 ID（精确匹配）                       |
| name      | string | 否  | 视频名称（模糊匹配）                        |
| status    | number | 否  | 状态：`1` 启用，`2` 禁用                  |
| beginTime | string | 否  | 创建时间起始（`created_at >= beginTime`） |
| endTime   | string | 否  | 创建时间结束（`created_at <= endTime`）   |

#### 响应示例 JSON

```json
{
    "code": 200,
    "msg": "查询成功",
    "total": 1,
    "rows": [
        {
            "videoId": 7100,
            "name": "系统示例视频",
            "status": 1,
            "statusText": "启用",
            "totalSize": "1048576",
            "totalSizeText": "1 MB",
            "length": 120,
            "lengthText": "00:02:00",
            "prefaceUrl": "https://cdn.example.com/video/7100-cover.jpg",
            "playUrl": "https://cdn.example.com/video/7100.mp4",
            "width": 1920,
            "height": 1080,
            "uploadTime": "2026-03-05 19:20:00",
            "createdAt": "2026-03-05 19:20:00"
        }
    ]
}
```

#### `rows` 字段说明

| 字段            | 类型           | 说明                |
|---------------|--------------|-------------------|
| videoId       | number       | 视频 ID             |
| name          | string       | 视频名称              |
| status        | number       | 状态：`1` 启用，`2` 禁用  |
| statusText    | string       | 状态文本              |
| totalSize     | string       | 视频大小（字节字符串）       |
| totalSizeText | string       | 格式化大小文本（如 `1 MB`） |
| length        | number       | 时长（秒）             |
| lengthText    | string       | 格式化时长（`HH:mm:ss`） |
| prefaceUrl    | string\|null | 封面地址              |
| playUrl       | string\|null | 播放地址              |
| width         | number       | 视频宽度              |
| height        | number       | 视频高度              |
| uploadTime    | string\|null | 上传时间（同创建时间）       |
| createdAt     | string\|null | 创建时间              |

---

### 3.3 获取系统视频详情

- 方法：`GET`
- 路径：`/api/admin/video/system/{videoId}`

#### Path 参数

| 参数      | 类型     | 必填 | 说明         |
|---------|--------|----|------------|
| videoId | number | 是  | 视频 ID（正整数） |

#### 响应示例 JSON

```json
{
    "code": 200,
    "msg": "查询成功",
    "data": {
        "videoId": 7100,
        "name": "系统示例视频",
        "status": 1,
        "statusText": "启用",
        "totalSize": "1048576",
        "totalSizeText": "1 MB",
        "length": 120,
        "lengthText": "00:02:00",
        "prefaceUrl": "https://cdn.example.com/video/7100-cover.jpg",
        "playUrl": "https://cdn.example.com/video/7100.mp4",
        "width": 1920,
        "height": 1080,
        "uploadTime": "2026-03-05 19:20:00",
        "createdAt": "2026-03-05 19:20:00",
        "updatedAt": "2026-03-05 20:00:00"
    }
}
```

#### `data` 字段说明

| 字段            | 类型           | 说明                |
|---------------|--------------|-------------------|
| videoId       | number       | 视频 ID             |
| name          | string       | 视频名称              |
| status        | number       | 状态：`1` 启用，`2` 禁用  |
| statusText    | string       | 状态文本              |
| totalSize     | string       | 视频大小（字节字符串）       |
| totalSizeText | string       | 格式化大小文本           |
| length        | number       | 时长（秒）             |
| lengthText    | string       | 格式化时长（`HH:mm:ss`） |
| prefaceUrl    | string\|null | 封面地址              |
| playUrl       | string\|null | 播放地址              |
| width         | number       | 视频宽度              |
| height        | number       | 视频高度              |
| uploadTime    | string\|null | 上传时间（同创建时间）       |
| createdAt     | string\|null | 创建时间              |
| updatedAt     | string\|null | 更新时间              |

---

### 3.4 新增系统视频

- 方法：`POST`
- 路径：`/api/admin/video/system`
- 说明：新增系统视频需要的参数在视频上传接口【/api/admin/common/uploadVideo】都有

#### Body 参数（JSON）

| 参数         | 类型     | 必填 | 说明                      |
|------------|--------|----|-------------------------|
| name       | string | 是  | 视频标题，最长 `255` 字符        |
| status     | number | 否  | 状态：`1` 启用，`2` 禁用，默认 `1` |
| totalSize  | string | 是  | 视频大小（字节字符串），最长 `50` 字符  |
| prefaceUrl | string | 是  | 封面地址，最长 `1024` 字符       |
| playUrl    | string | 是  | 播放地址，最长 `512` 字符        |
| length     | number | 是  | 时长（秒），`>= 0`            |
| width      | number | 是  | 视频宽度，`>= 0`             |
| height     | number | 是  | 视频高度，`>= 0`             |

#### 请求示例 JSON

```json
{
    "name": "44hGCXRIAcIA.mp4",
    "totalSize": 19096475,
    "status": 1,
    "prefaceUrl": "https://dev-hobby-app.tos-cn-beijing.volces.com/admin/video-cover/20260318/5ae28c85-532f-4a47-bee1-6a832c36adcc.jpg",
    "playUrl": "https://dev-hobby-app.tos-cn-beijing.volces.com/admin/video/20260318/5ae28c85-532f-4a47-bee1-6a832c36adcc.mp4",
    "length": 35,
    "width": 1280,
    "height": 720
}
```

#### 响应示例 JSON（成功）

```json
{
    "code": 200,
    "msg": "新增成功",
    "data": {
        "videoId": 7101
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

---

### 3.5 更新系统视频

- 方法：`PUT`
- 路径：`/api/admin/video/system`

#### Body 参数（JSON）

| 参数      | 类型     | 必填 | 说明               |
|---------|--------|----|------------------|
| videoId | number | 是  | 视频 ID，`>= 1`     |
| name    | string | 否  | 视频标题，最长 `255` 字符 |
| status  | number | 否  | 状态：`1` 启用，`2` 禁用 |

#### 请求示例 JSON

```json
{
    "videoId": 1,
    "name": "44hGCXRIAcIA",
    "status": 1
}
```

#### 响应示例 JSON（成功）

```json
{
    "code": 200,
    "msg": "修改成功",
    "data": []
}
```

#### 响应示例 JSON（失败）

```json
{
    "code": 6000,
    "msg": "视频不存在",
    "data": []
}
```

---

### 3.6 删除系统视频-不支持批量

- 方法：`DELETE`
- 路径：`/api/admin/video/system/{videoId}`

#### Path 参数

| 参数      | 类型     | 必填 | 说明   |
|---------|--------|----|------|
| videoId | number | 是  | 视频ID |

#### 响应示例 JSON（成功）

```json
{
    "code": 200,
    "msg": "删除成功",
    "data": []
}
```

#### 响应示例 JSON（失败：参数错误）

```json
{
    "code": 6000,
    "msg": "参数错误",
    "data": []
}
```

#### 响应示例 JSON（失败：视频不存在）

```json
{
    "code": 6000,
    "msg": "删除失败，视频不存在",
    "data": []
}
```
