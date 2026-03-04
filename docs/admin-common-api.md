# Admin 模块通用上传接口文档

## 1. 接口列表总览
| 功能 | 方法 | 路径 |
| --- | --- | --- |
| 单张图片上传 | POST | `/api/admin/common/uploadImage` |
| 多张图片上传 | POST | `/api/admin/common/uploadImages` |
| 视频上传 | POST | `/api/admin/common/uploadVideo` |

## 2. 通用说明
- 鉴权：所有接口都需要 `Authorization: Bearer {token}`
- 请求类型：`multipart/form-data`

### 2.1 请求头建议

```http
Accept: application/json
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

### 2.2 通用响应示例

#### 成功响应（单文件）
```json
{
  "code": 200,
  "msg": "上传成功",
  "data": {
    "fileName": "banner.png",
    "key": "admin/image/20260304/9a553937-63b2-4f60-b4db-55fded6f6e14.png",
    "size": 32566,
    "url": "https://cdn.example.com/admin/image/20260304/9a553937-63b2-4f60-b4db-55fded6f6e14.png"
  }
}
```

#### 成功响应（多文件）
```json
{
  "code": 200,
  "msg": "上传成功",
  "data": {
    "errors": [],
    "failed": 0,
    "results": [
      {
        "fileName": "a.png",
        "index": 0,
        "key": "admin/image/20260304/2f2f7f7f-9f3b-4aac-a34e-cc9e9f4c2aa2.png",
        "size": 23456,
        "url": "https://cdn.example.com/admin/image/20260304/2f2f7f7f-9f3b-4aac-a34e-cc9e9f4c2aa2.png"
      }
    ],
    "success": 1,
    "total": 1
  }
}
```

#### 失败响应
```json
{
  "code": 1201,
  "msg": "仅支持上传图片文件",
  "data": []
}
```

## 3. 详细接口说明

### 3.1 单张图片上传
- 方法：`POST`
- 路径：`/api/admin/common/uploadImage`
- 说明：上传一张图片文件，返回文件信息（文件名、存储 key、大小、访问 URL）。

#### Body 参数（multipart/form-data）
| 参数 | 类型 | 必填 | 说明 |
| --- | --- | --- | --- |
| file | file | 是 | 图片文件，支持 MIME：`image/jpeg`、`image/png`、`image/gif`、`image/webp`、`image/bmp` |

#### 响应示例 JSON（成功）
```json
{
  "code": 200,
  "msg": "上传成功",
  "data": {
    "fileName": "banner.png",
    "key": "admin/image/20260304/9a553937-63b2-4f60-b4db-55fded6f6e14.png",
    "size": 32566,
    "url": "https://cdn.example.com/admin/image/20260304/9a553937-63b2-4f60-b4db-55fded6f6e14.png"
  }
}
```

#### 响应示例 JSON（失败）
```json
{
  "code": 1201,
  "msg": "请选择要上传的图片",
  "data": []
}
```

#### `data` 字段说明
| 字段 | 类型 | 说明 |
| --- | --- | --- |
| fileName | string | 原始文件名 |
| key | string | 文件存储路径 |
| size | number | 文件大小（字节） |
| url | string | 文件可访问地址 |

---

### 3.2 多张图片上传
- 方法：`POST`
- 路径：`/api/admin/common/uploadImages`
- 说明：批量上传图片，单次最多 `9` 张。

#### Body 参数（multipart/form-data）
| 参数 | 类型 | 必填 | 说明 |
| --- | --- | --- | --- |
| files | file[] | 是 | 图片文件数组（可通过 `files[]` 方式提交多个文件） |

#### 文件类型约束
- 支持 MIME：`image/jpeg`、`image/png`、`image/gif`、`image/webp`、`image/bmp`

#### 响应示例 JSON（成功）
```json
{
  "code": 200,
  "msg": "上传成功",
  "data": {
    "errors": [
      {
        "index": 2,
        "fileName": "bad-file.png",
        "error": "上传失败"
      }
    ],
    "failed": 1,
    "results": [
      {
        "fileName": "good-1.png",
        "index": 0,
        "key": "admin/image/20260304/aa3bd6cf-4400-4a91-b87f-793305bc2a16.png",
        "size": 105234,
        "url": "https://cdn.example.com/admin/image/20260304/aa3bd6cf-4400-4a91-b87f-793305bc2a16.png"
      },
      {
        "fileName": "good-2.png",
        "index": 1,
        "key": "admin/image/20260304/3f086fa4-c4e4-4cd2-804f-71a3028bec85.png",
        "size": 96331,
        "url": "https://cdn.example.com/admin/image/20260304/3f086fa4-c4e4-4cd2-804f-71a3028bec85.png"
      }
    ],
    "success": 2,
    "total": 3
  }
}
```

#### 响应示例 JSON（失败）
```json
{
  "code": 1201,
  "msg": "文件验证失败,请重试",
  "data": []
}
```

#### `data` 字段说明
| 字段 | 类型 | 说明 |
| --- | --- | --- |
| total | number | 本次上传文件总数 |
| success | number | 上传成功数量 |
| failed | number | 上传失败数量 |
| results | array | 成功文件列表 |
| errors | array | 失败文件列表 |

`results` 子项字段：

| 字段 | 类型 | 说明 |
| --- | --- | --- |
| fileName | string | 原始文件名 |
| index | number | 成功结果中的顺序索引（从 `0` 开始） |
| key | string | 文件存储路径 |
| size | number | 文件大小（字节） |
| url | string | 文件可访问地址 |

`errors` 子项字段：

| 字段 | 类型 | 说明 |
| --- | --- | --- |
| index | number | 原始上传文件索引（从 `0` 开始） |
| fileName | string | 原始文件名 |
| error | string | 失败原因（当前固定返回 `上传失败`） |

---

### 3.3 视频上传
- 方法：`POST`
- 路径：`/api/admin/common/uploadVideo`
- 说明：上传单个视频文件，返回文件信息（文件名、存储 key、大小、访问 URL）。

#### Body 参数（multipart/form-data）
| 参数 | 类型 | 必填 | 说明 |
| --- | --- | --- | --- |
| file | file | 是 | 视频文件，支持 MIME：`video/mp4`、`video/quicktime`、`video/x-msvideo`、`video/x-ms-wmv`、`video/webm`、`video/mpeg` |

#### 响应示例 JSON（成功）
```json
{
  "code": 200,
  "msg": "上传成功",
  "data": {
    "fileName": "intro.mp4",
    "key": "admin/video/20260304/85fcefeb-a55f-4388-8fb0-067ea0701897.mp4",
    "size": 2405137,
    "url": "https://cdn.example.com/admin/video/20260304/85fcefeb-a55f-4388-8fb0-067ea0701897.mp4"
  }
}
```

#### 响应示例 JSON（失败）
```json
{
  "code": 1201,
  "msg": "仅支持上传视频文件",
  "data": []
}
```

#### `data` 字段说明
| 字段 | 类型 | 说明 |
| --- | --- | --- |
| fileName | string | 原始文件名 |
| key | string | 文件存储路径 |
| size | number | 文件大小（字节） |
| url | string | 文件可访问地址 |
