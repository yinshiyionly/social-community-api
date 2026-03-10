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
| 批量修改分类排序 | PUT | `/api/admin/course/category/batchSort` |
| 删除分类 | DELETE | `/api/admin/course/category/{categoryId}` |

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
| status | number | 否 | 状态：`1` 启用，`2` 禁用 |
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
      "categoryName": "健康课程",
      "icon": "https://cdn.example.com/icon/health.png",
      "sortOrder": 100,
      "status": 1,
      "createdAt": "2026-02-18 14:20:00"
    },
    {
      "categoryId": 11,
      "categoryName": "中医养生",
      "icon": "https://cdn.example.com/icon/tcm.png",
      "sortOrder": 90,
      "status": 1,
      "createdAt": "2026-02-19 10:30:00"
    }
  ]
}
```

#### `rows` 字段说明
| 字段 | 类型 | 说明 |
| --- | --- | --- |
| categoryId | number | 分类 ID |
| categoryName | string | 分类名称 |
| icon | string\|null | 图标地址 |
| sortOrder | number | 排序值（降序） |
| status | number | 状态：`1` 启用，`2` 禁用 |
| createdAt | string\|null | 创建时间，格式：`Y-m-d H:i:s` |

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
      "categoryName": "健康课程",
      "icon": "https://cdn.example.com/icon/health.png",
      "sortOrder": 100,
      "status": 1,
      "createdAt": "2026-02-18 14:20:00"
    },
    {
      "categoryId": 11,
      "categoryName": "中医养生",
      "icon": "https://cdn.example.com/icon/tcm.png",
      "sortOrder": 90,
      "status": 1,
      "createdAt": "2026-02-19 10:30:00"
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
      "categoryName": "健康课程"
    },
    {
      "categoryId": 11,
      "categoryName": "中医养生"
    }
  ]
}
```

#### `data` 字段说明
| 字段 | 类型 | 说明 |
| --- | --- | --- |
| categoryId | number | 分类 ID |
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
    "categoryName": "中医养生",
    "icon": "https://cdn.example.com/icon/tcm.png",
    "sortOrder": 90,
    "status": 1,
    "createBy": 1,
    "updateBy": 1,
    "createdAt": "2026-02-19 10:30:00",
    "updatedAt": "2026-02-20 09:00:00"
  }
}
```

#### `data` 字段说明
| 字段 | 类型 | 说明 |
| --- | --- | --- |
| categoryId | number | 分类 ID |
| categoryName | string | 分类名称 |
| icon | string\|null | 图标地址 |
| sortOrder | number | 排序值 |
| status | number | 状态：`1` 启用，`2` 禁用 |
| createBy | number\|null | 创建人 ID |
| updateBy | number\|null | 更新人 ID |
| createdAt | string\|null | 创建时间 |
| updatedAt | string\|null | 更新时间 |

---

### 3.5 新增分类
- 方法：`POST`
- 路径：`/api/admin/course/category`

#### Body 参数（JSON）
| 参数 | 类型 | 必填 | 说明 |
| --- | --- | --- | --- |
| categoryName | string | 是 | 分类名称，最长 `50` 字符，且唯一 |
| icon | string | 是 | 图标地址，最长 `255` 字符 |
| status | number | 是 | 状态：`1` 启用，`2` 禁用 |

#### 请求示例 JSON
```json
{
  "categoryName": "运动康复",
  "icon": "https://cdn.example.com/icon/recovery.png",
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
  "msg": "分类名称已存在",
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
| categoryName | string | 是 | 分类名称，最长 `50` 字符，且唯一（排除自身） |
| icon | string | 是 | 图标地址，最长 `255` 字符 |
| status | number | 是 | 状态：`1` 启用，`2` 禁用 |

#### 请求示例 JSON
```json
{
  "categoryId": 11,
  "categoryName": "中医养生课程",
  "icon": "https://cdn.example.com/icon/tcm-new.png",
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
  "msg": "分类名称已存在",
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

### 3.8 批量修改分类排序
- 方法：`PUT`
- 路径：`/api/admin/course/category/batchSort`

#### Body 参数（JSON）
| 参数                      | 类型 | 必填 | 说明 |
|-------------------------| --- | --- | --- |
| category                | array | 是 | 分类排序数组，至少 1 项 |
| category[].categoryId   | number | 是 | 分类 ID，`>= 1`，且不能重复 |
| category[].categorySort | number | 是 | 排序值，`>= 0` |

#### 请求示例 JSON
```json
{
  "category": [
    {
      "categoryId": 1,
      "categorySort": 999
    },
    {
      "categoryId": 8,
      "categorySort": 0
    },
    {
      "categoryId": 7,
      "categorySort": 0
    },
    {
      "categoryId": 4,
      "categorySort": 0
    },
    {
      "categoryId": 2,
      "categorySort": 0
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

#### 响应示例 JSON（失败）
```json
{
  "code": 1202,
  "msg": "排序数据不能为空",
  "data": []
}
```

---

### 3.9 删除分类
- 方法：`DELETE`
- 路径：`/api/admin/course/category/{categoryId}`

#### Path 参数
| 参数 | 类型 | 必填 | 说明 |
| --- | --- | --- | --- |
| categoryId | number | 是 | 分类 ID（正整数） |

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
  "msg": "分类下存在正常课程，无法删除",
  "data": []
}
```

#### 响应示例 JSON（失败：分类不存在）
```json
{
  "code": 1201,
  "msg": "删除失败，分类不存在",
  "data": []
}
```
