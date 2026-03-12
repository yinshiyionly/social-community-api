# Admin 模块广告模块接口文档

## 1. 接口列表总览

| 功能 | 方法 | 路径 |
|---|---|---|
| 广告模块常量选项 | GET | `/api/admin/ad/constants` |
| 广告位分页列表 | GET | `/api/admin/ad/space/list` |
| 广告位下拉选项 | GET | `/api/admin/ad/space/optionselect` |
| 广告位详情 | GET | `/api/admin/ad/space/{spaceId}` |
| 新增广告位 | POST | `/api/admin/ad/space` |
| 更新广告位 | PUT | `/api/admin/ad/space` |
| 删除广告位 | DELETE | `/api/admin/ad/space/{spaceId}` |
| 广告内容分页列表 | GET | `/api/admin/ad/item/list` |
| 广告内容详情 | GET | `/api/admin/ad/item/{adId}` |
| 新增广告内容 | POST | `/api/admin/ad/item` |
| 更新广告内容 | PUT | `/api/admin/ad/item` |
| 批量排序广告内容 | PUT | `/api/admin/ad/item/batchSort` |
| 删除广告内容 | DELETE | `/api/admin/ad/item/{adId}` |

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

### 2.2 枚举说明

| 枚举 | 值 | 说明 |
|---|---|---|
| spaceStatus | `1` | 启用 |
| spaceStatus | `2` | 禁用 |
| adType | `image` | 图片 |
| adType | `video` | 视频 |
| adType | `text` | 文本 |
| adType | `html` | HTML |
| targetType | `external` | 外部链接 |
| targetType | `internal` | 内部页面 |
| targetType | `none` | 不跳转 |
| adStatus | `1` | 上线 |
| adStatus | `2` | 下线 |

## 3. 详细接口说明

### 3.2 广告模块常量选项

- 方法：`GET`
- 路径：`/api/admin/ad/constants`
- 参数：无
- 说明：返回广告管理表单需要的全部枚举选项。

#### 响应示例 JSON

```json
{
    "code": 200,
    "msg": "操作成功",
    "data": {
        "spaceStatusOptions": [
            {
                "label": "启用",
                "value": 1
            },
            {
                "label": "禁用",
                "value": 2
            }
        ],
        "adTypeOptions": [
            {
                "label": "图片",
                "value": "image"
            },
            {
                "label": "视频",
                "value": "video"
            },
            {
                "label": "文本",
                "value": "text"
            },
            {
                "label": "HTML",
                "value": "html"
            }
        ],
        "targetTypeOptions": [
            {
                "label": "外部链接",
                "value": "external"
            },
            {
                "label": "内部页面",
                "value": "internal"
            },
            {
                "label": "不跳转",
                "value": "none"
            }
        ],
        "adStatusOptions": [
            {
                "label": "上线",
                "value": 1
            },
            {
                "label": "下线",
                "value": 2
            }
        ]
    }
}
```

### 3.3 广告位分页列表

- 方法：`GET`
- 路径：`/api/admin/ad/space/list`

#### Query 参数

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| pageNum | number | 否 | 页码，默认 `1` |
| pageSize | number | 否 | 每页条数，默认 `10` |
| spaceName | string | 否 | 广告位名称（模糊搜索） |
| spaceCode | string | 否 | 广告位编码（模糊搜索） |
| status | number | 否 | 广告位状态：`1` 启用、`2` 禁用 |

#### 响应示例 JSON

```json
{
    "code": 200,
    "msg": "查询成功",
    "total": 1,
    "rows": [
        {
            "spaceId": 1,
            "spaceName": "首页轮播",
            "spaceCode": "home_banner",
            "width": 750,
            "height": 340,
            "maxAds": 5,
            "status": 1,
            "createdAt": "2026-03-11 10:30:00"
        }
    ]
}
```

### 3.4 广告位下拉选项

- 方法：`GET`
- 路径：`/api/admin/ad/space/optionselect`
- 参数：无
- 说明：仅返回启用状态广告位，用于广告内容创建/编辑表单下拉选择。

#### 响应示例 JSON

```json
{
    "code": 200,
    "msg": "查询成功",
    "data": [
        {
            "spaceId": 1,
            "spaceName": "首页轮播",
            "spaceCode": "home_banner"
        }
    ]
}
```

### 3.5 广告位详情

- 方法：`GET`
- 路径：`/api/admin/ad/space/{spaceId}`

#### Path 参数

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| spaceId | number | 是 | 广告位 ID（正整数） |

#### 响应示例 JSON

```json
{
    "code": 200,
    "msg": "查询成功",
    "data": {
        "spaceId": 1,
        "spaceName": "首页轮播",
        "spaceCode": "home_banner",
        "width": 750,
        "height": 340,
        "maxAds": 5,
        "status": 1,
        "createdAt": "2026-03-11 10:30:00",
        "updatedAt": "2026-03-11 10:35:00"
    }
}
```

### 3.6 新增广告位

- 方法：`POST`
- 路径：`/api/admin/ad/space`

#### Body 参数（JSON）

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| spaceName | string | 是 | 广告位名称，最长 `50` 字符 |
| spaceCode | string | 是 | 广告位编码，最长 `50` 字符，唯一 |
| width | number | 否 | 宽度，最小 `0`，默认 `0` |
| height | number | 否 | 高度，最小 `0`，默认 `0` |
| maxAds | number | 否 | 最大广告数，最小 `0`，默认 `0` |
| status | number | 否 | 状态：`1` 启用、`2` 禁用，默认 `1` |

#### 请求示例 JSON

```json
{
    "spaceName": "学习中心顶部轮播",
    "spaceCode": "learning_top_banner",
    "width": 750,
    "height": 340,
    "maxAds": 5,
    "status": 1
}
```

#### 响应示例 JSON（成功）

```json
{
    "code": 200,
    "msg": "新增成功",
    "data": []
}
```

### 3.7 更新广告位

- 方法：`PUT`
- 路径：`/api/admin/ad/space`

#### Body 参数（JSON）

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| spaceId | number | 是 | 广告位 ID，`>= 1` |
| spaceName | string | 是 | 广告位名称，最长 `50` 字符 |
| spaceCode | string | 是 | 广告位编码，最长 `50` 字符，唯一（排除自身） |
| width | number | 否 | 宽度，最小 `0` |
| height | number | 否 | 高度，最小 `0` |
| maxAds | number | 否 | 最大广告数，最小 `0` |
| status | number | 否 | 状态：`1` 启用、`2` 禁用 |

#### 响应示例 JSON（成功）

```json
{
    "code": 200,
    "msg": "修改成功",
    "data": []
}
```

### 3.9 删除广告位

- 方法：`DELETE`
- 路径：`/api/admin/ad/space/{spaceId}`

#### Path 参数

| 参数 | 类型     | 必填 | 说明 |
|---|--------|---|----|
| spaceId | number | 是 | 1  |

#### 响应示例 JSON（失败）

```json
{
    "code": 1201,
    "msg": "广告位下存在广告内容，无法删除",
    "data": []
}
```

### 3.10 广告内容分页列表

- 方法：`GET`
- 路径：`/api/admin/ad/item/list`

#### Query 参数

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| pageNum | number | 否 | 页码，默认 `1` |
| pageSize | number | 否 | 每页条数，默认 `10` |
| spaceId | number | 否 | 广告位 ID（精确匹配） |
| adTitle | string | 否 | 广告标题（模糊搜索） |
| adType | string | 否 | 素材类型：`image/video/text/html` |
| status | number | 否 | 状态：`1` 上线、`2` 下线 |
| beginTime | string | 否 | 创建时间起始（`created_at >= beginTime`） |
| endTime | string | 否 | 创建时间结束（`created_at <= endTime`） |

#### 响应示例 JSON

```json
{
    "code": 200,
    "msg": "查询成功",
    "total": 1,
    "rows": [
        {
            "adId": 11,
            "spaceId": 1,
            "spaceName": "首页轮播",
            "adTitle": "春季活动 Banner",
            "adType": "image",
            "contentUrl": "https://cdn.example.com/ad/2026/spring.png",
            "targetType": "external",
            "targetUrl": "https://example.com/activity/spring",
            "sortNum": 100,
            "status": 1,
            "startTime": "2026-03-01 00:00:00",
            "endTime": "2026-03-31 23:59:59",
            "createdAt": "2026-03-10 14:20:00"
        }
    ]
}
```

### 3.11 广告内容详情

- 方法：`GET`
- 路径：`/api/admin/ad/item/{adId}`

#### Path 参数

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| adId | number | 是 | 广告 ID（正整数） |

#### 响应示例 JSON

```json
{
    "code": 200,
    "msg": "查询成功",
    "data": {
        "adId": 11,
        "spaceId": 1,
        "spaceName": "首页轮播",
        "adTitle": "春季活动 Banner",
        "adType": "image",
        "contentUrl": "https://cdn.example.com/ad/2026/spring.png",
        "targetType": "external",
        "targetUrl": "https://example.com/activity/spring",
        "sortNum": 100,
        "status": 1,
        "startTime": "2026-03-01 00:00:00",
        "endTime": "2026-03-31 23:59:59",
        "extJson": {
            "trackCode": "spring-2026"
        },
        "createdAt": "2026-03-10 14:20:00",
        "updatedAt": "2026-03-10 16:00:00"
    }
}
```

### 3.12 新增广告内容

- 方法：`POST`
- 路径：`/api/admin/ad/item`

#### Body 参数（JSON）

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| spaceId | number | 是 | 广告位 ID，`>= 1` |
| adTitle | string | 是 | 广告标题，最长 `100` 字符 |
| adType | string | 是 | 素材类型：`image/video/text/html` |
| contentUrl | string | 否 | 素材地址 |
| targetType | string | 否 | 跳转类型：`external/internal/none`，默认 `none` |
| targetUrl | string | 否 | 跳转地址，最长 `500` 字符 |
| sortNum | number | 否 | 排序值，最小 `0`，默认 `0` |
| status | number | 否 | 状态：`1` 上线、`2` 下线，默认 `1` |
| startTime | string | 否 | 生效时间（`Y-m-d H:i:s`） |
| endTime | string | 否 | 失效时间（`>= startTime`） |
| extJson | object/array | 否 | 扩展字段 |

#### 请求示例 JSON

```json
{
    "spaceId": 1,
    "adTitle": "春季活动 Banner",
    "adType": "image",
    "contentUrl": "https://cdn.example.com/ad/2026/spring.png",
    "targetType": "external",
    "targetUrl": "https://example.com/activity/spring",
    "sortNum": 100,
    "status": 1,
    "startTime": "2026-03-01 00:00:00",
    "endTime": "2026-03-31 23:59:59",
    "extJson": {
        "trackCode": "spring-2026"
    }
}
```

#### 响应示例 JSON（成功）

```json
{
    "code": 200,
    "msg": "新增成功",
    "data": []
}
```

### 3.13 更新广告内容

- 方法：`PUT`
- 路径：`/api/admin/ad/item`

#### Body 参数（JSON）

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| adId | number | 是 | 广告 ID，`>= 1` |
| spaceId | number | 是 | 广告位 ID，`>= 1` |
| adTitle | string | 是 | 广告标题，最长 `100` 字符 |
| adType | string | 是 | 素材类型：`image/video/text/html` |
| contentUrl | string | 否 | 素材地址 |
| targetType | string | 否 | 跳转类型：`external/internal/none` |
| targetUrl | string | 否 | 跳转地址，最长 `500` 字符 |
| sortNum | number | 否 | 排序值，最小 `0` |
| status | number | 否 | 状态：`1` 上线、`2` 下线 |
| startTime | string | 否 | 生效时间 |
| endTime | string | 否 | 失效时间（`>= startTime`） |
| extJson | object/array | 否 | 扩展字段 |

#### 响应示例 JSON（成功）

```json
{
    "code": 200,
    "msg": "修改成功",
    "data": []
}
```

### 3.15 批量排序广告内容

- 方法：`PUT`
- 路径：`/api/admin/ad/item/batchSort`
- 说明：一次提交多条广告排序，接口在事务内执行。

#### Body 参数（JSON）

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| items | array | 是 | 排序数组，最少 1 条 |
| items[].adId | number | 是 | 广告 ID，`>= 1`，同批次不可重复 |
| items[].sortNum | number | 是 | 排序值，最小 `0` |

#### 请求示例 JSON

```json
{
    "items": [
        {
            "adId": 11,
            "sortNum": 200
        },
        {
            "adId": 12,
            "sortNum": 180
        }
    ]
}
```

#### 响应示例 JSON（成功）

```json
{
    "code": 200,
    "msg": "排序成功",
    "data": []
}
```

### 3.16 删除广告内容

- 方法：`DELETE`
- 路径：`/api/admin/ad/item/{adId}`

#### Path 参数

| 参数 | 类型    | 必填 | 说明 |
|---|-------|---|----|
| adId | numer | 是 | 11 |

#### 响应示例 JSON（成功）

```json
{
    "code": 200,
    "msg": "删除成功",
    "data": []
}
```
