# Admin 模块课程分类接口文档

## 1. 接口列表总览
| 功能 | 方法 | 路径 |
| --- | --- | --- |
| 分类分页列表 | GET | `/api/admin/course/category/list` |
| 分类树形列表 | GET | `/api/admin/course/category/treeList` |
| 分类下拉选项 | GET | `/api/admin/course/category/optionselect` |
| 分类详情 | GET | `/api/admin/course/category/{categoryId}` |
| 新增分类 | POST | `/api/admin/course/category` |
| 更新分类 | PUT | `/api/admin/course/category` |
| 修改分类状态 | PUT | `/api/admin/course/category/changeStatus` |
| 删除分类（支持批量） | DELETE | `/api/admin/course/category/{categoryIds}` |

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
  "data": []
}
```

#### 失败响应
```json
{
  "code": 1201,
  "msg": "分类不存在",
  "data": []
}
```

## 3. 详细接口说明

### 3.1 获取分类分页列表
- 方法：`GET`
- 路径：`/api/admin/course/category/list`

#### Query 参数
| 参数 | 类型 | 必填 | 说明 |
| --- | --- | --- | --- |
| pageNum | number | 否 | 页码，默认 `1` |
| pageSize | number | 否 | 每页条数，默认 `10` |
| categoryName | string | 否 | 分类名称（模糊搜索） |
| categoryCode | string | 否 | 分类编码（模糊搜索） |
| status | number | 否 | 状态：`1` 启用，`2` 禁用 |
| parentId | number | 否 | 父分类 ID |
| beginTime | string | 否 | 创建时间起始（`created_at >= beginTime`） |
| endTime | string | 否 | 创建时间结束（`created_at <= endTime`） |

#### 响应示例 JSON
```json
{
  "code": 200,
  "msg": "查询成功",
  "total": 2,
  "rows": [
    {
      "categoryId": 10,
      "parentId": 0,
      "categoryName": "健康课程",
      "categoryCode": "HEALTH",
      "icon": "https://cdn.example.com/icon/health.png",
      "cover": "https://cdn.example.com/cover/health.png",
      "sortOrder": 100,
      "status": 1,
      "createTime": "2026-02-18 14:20:00"
    },
    {
      "categoryId": 11,
      "parentId": 10,
      "categoryName": "中医养生",
      "categoryCode": "TCM",
      "icon": null,
      "cover": null,
      "sortOrder": 90,
      "status": 1,
      "createTime": "2026-02-19 10:30:00"
    }
  ]
}
```

#### `rows` 字段说明
| 字段 | 类型 | 说明 |
| --- | --- | --- |
| categoryId | number | 分类 ID |
| parentId | number | 父分类 ID，顶级分类为 `0` |
| categoryName | string | 分类名称 |
| categoryCode | string | 分类编码 |
| icon | string\|null | 图标地址 |
| cover | string\|null | 封面地址 |
| sortOrder | number | 排序值（降序） |
| status | number | 状态：`1` 启用，`2` 禁用 |
| createTime | string\|null | 创建时间，格式：`Y-m-d H:i:s` |

---

### 3.2 获取分类树形列表
- 方法：`GET`
- 路径：`/api/admin/course/category/treeList`
- 参数：无

#### 响应示例 JSON
```json
{
  "code": 200,
  "msg": "查询成功",
  "data": [
    {
      "categoryId": 10,
      "parentId": 0,
      "categoryName": "健康课程",
      "categoryCode": "HEALTH",
      "icon": "https://cdn.example.com/icon/health.png",
      "cover": "https://cdn.example.com/cover/health.png",
      "sortOrder": 100,
      "status": 1,
      "createTime": "2026-02-18 14:20:00"
    },
    {
      "categoryId": 11,
      "parentId": 10,
      "categoryName": "中医养生",
      "categoryCode": "TCM",
      "icon": null,
      "cover": null,
      "sortOrder": 90,
      "status": 1,
      "createTime": "2026-02-19 10:30:00"
    }
  ]
}
```
---

### 3.3 获取分类下拉选项
- 方法：`GET`
- 路径：`/api/admin/course/category/optionselect`
- 参数：无

#### 响应示例 JSON
```json
{
  "code": 200,
  "msg": "查询成功",
  "data": [
    {
      "categoryId": 10,
      "parentId": 0,
      "categoryName": "健康课程"
    },
    {
      "categoryId": 11,
      "parentId": 10,
      "categoryName": "中医养生"
    }
  ]
}
```

#### `data` 字段说明
| 字段 | 类型 | 说明 |
| --- | --- | --- |
| categoryId | number | 分类 ID |
| parentId | number | 父分类 ID |
| categoryName | string | 分类名称 |

---

### 3.4 获取分类详情
- 方法：`GET`
- 路径：`/api/admin/course/category/{categoryId}`

#### Path 参数
| 参数 | 类型 | 必填 | 说明 |
| --- | --- | --- | --- |
| categoryId | number | 是 | 分类 ID（正整数） |

#### 响应示例 JSON
```json
{
  "code": 200,
  "msg": "查询成功",
  "data": {
    "categoryId": 11,
    "parentId": 10,
    "categoryName": "中医养生",
    "categoryCode": "TCM",
    "icon": "https://cdn.example.com/icon/tcm.png",
    "cover": "https://cdn.example.com/cover/tcm.png",
    "description": "中医相关课程分类",
    "sortOrder": 90,
    "status": 1,
    "createBy": 1,
    "updateBy": 1,
    "createTime": "2026-02-19 10:30:00",
    "updateTime": "2026-02-20 09:00:00",
    "parentName": "健康课程"
  }
}
```

#### `data` 字段说明
| 字段 | 类型 | 说明 |
| --- | --- | --- |
| categoryId | number | 分类 ID |
| parentId | number | 父分类 ID |
| categoryName | string | 分类名称 |
| categoryCode | string | 分类编码 |
| icon | string\|null | 图标地址 |
| cover | string\|null | 封面地址 |
| description | string\|null | 分类描述 |
| sortOrder | number | 排序值 |
| status | number | 状态：`1` 启用，`2` 禁用 |
| createBy | number\|null | 创建人 ID |
| updateBy | number\|null | 更新人 ID |
| createTime | string\|null | 创建时间 |
| updateTime | string\|null | 更新时间 |
| parentName | string\|null | 父分类名称 |

---

### 3.5 新增分类
- 方法：`POST`
- 路径：`/api/admin/course/category`

#### Body 参数（JSON）
| 参数 | 类型 | 必填 | 说明 |
| --- | --- | --- | --- |
| parentId | number | 否 | 父分类 ID，`>= 0`，默认 `0` |
| categoryName | string | 是 | 分类名称，最长 `50` 字符，且唯一 |
| categoryCode | string | 否 | 分类编码，最长 `50` 字符（业务上要求唯一） |
| icon | string | 否 | 图标地址，最长 `255` 字符 |
| cover | string | 否 | 封面地址，最长 `255` 字符 |
| description | string | 否 | 描述，最长 `500` 字符 |
| sortOrder | number | 否 | 排序值，`>= 0`，默认 `0` |
| status | number | 否 | 状态：`1` 启用，`2` 禁用，默认 `1` |

#### 请求示例 JSON
```json
{
  "parentId": 10,
  "categoryName": "运动康复",
  "categoryCode": "SPORT_RECOVERY",
  "icon": "https://cdn.example.com/icon/recovery.png",
  "cover": "https://cdn.example.com/cover/recovery.png",
  "description": "运动康复相关课程",
  "sortOrder": 80,
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

#### 响应示例 JSON（失败）
```json
{
  "code": 1201,
  "msg": "分类编码已存在",
  "data": []
}
```

---

### 3.6 更新分类
- 方法：`PUT`
- 路径：`/api/admin/course/category`

#### Body 参数（JSON）
| 参数 | 类型 | 必填 | 说明 |
| --- | --- | --- | --- |
| categoryId | number | 是 | 分类 ID，`>= 1` |
| parentId | number | 否 | 父分类 ID，`>= 0` |
| categoryName | string | 是 | 分类名称，最长 `50` 字符，且唯一（排除自身） |
| categoryCode | string | 否 | 分类编码，最长 `50` 字符（业务上要求唯一，排除自身） |
| icon | string | 否 | 图标地址，最长 `255` 字符 |
| cover | string | 否 | 封面地址，最长 `255` 字符 |
| description | string | 否 | 描述，最长 `500` 字符 |
| sortOrder | number | 否 | 排序值，`>= 0` |
| status | number | 否 | 状态：`1` 启用，`2` 禁用 |

#### 请求示例 JSON
```json
{
  "categoryId": 11,
  "parentId": 10,
  "categoryName": "中医养生课程",
  "categoryCode": "TCM",
  "icon": "https://cdn.example.com/icon/tcm-new.png",
  "cover": "https://cdn.example.com/cover/tcm-new.png",
  "description": "更新后的分类描述",
  "sortOrder": 95,
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
  "code": 1201,
  "msg": "不能将自己设为父分类",
  "data": []
}
```

---

### 3.7 修改分类状态
- 方法：`PUT`
- 路径：`/api/admin/course/category/changeStatus`

#### Body 参数（JSON）
| 参数 | 类型 | 必填 | 说明 |
| --- | --- | --- | --- |
| categoryId | number | 是 | 分类 ID，`>= 1` |
| status | number | 是 | 状态：`1` 启用，`2` 禁用 |

#### 请求示例 JSON
```json
{
  "categoryId": 11,
  "status": 2
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
  "code": 1201,
  "msg": "分类不存在",
  "data": []
}
```

---

### 3.8 删除分类（支持批量）
- 方法：`DELETE`
- 路径：`/api/admin/course/category/{categoryIds}`

#### Path 参数
| 参数 | 类型 | 必填 | 说明 |
| --- | --- | --- | --- |
| categoryIds | string | 是 | 分类 ID 字符串；单个如 `1`，批量如 `1,2,3` |

#### 响应示例 JSON（成功）
```json
{
  "code": 200,
  "msg": "删除成功",
  "data": []
}
```

#### 响应示例 JSON（失败）
```json
{
  "code": 1201,
  "msg": "存在子分类，无法删除",
  "data": []
}
```
