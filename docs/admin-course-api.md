# Admin 模块课程接口文档

## 1. 接口列表总览
| 功能 | 方法 | 路径 |
| --- | --- | --- |
| 课程常量选项 | GET | `/api/admin/course/constants` |
| 课程分页列表 | GET | `/api/admin/course/list` |
| 课程下拉选项 | GET | `/api/admin/course/optionselect` |
| 课程详情 | GET | `/api/admin/course/{courseId}` |
| 新增课程 | POST | `/api/admin/course` |
| 更新课程 | PUT | `/api/admin/course` |
| 修改课程状态 | PUT | `/api/admin/course/changeStatus` |
| 批量修改课程排序 | PUT | `/api/admin/course/batchSort` |
| 删除课程（支持批量） | DELETE | `/api/admin/course/{courseIds}` |

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

### 3.1 获取课程常量选项
- 方法：`GET`
- 路径：`/api/admin/course/constants`
- 参数：无
- 说明：返回课程表单所需的枚举选项（付费类型、播放类型、排课类型、状态），前端可直接用于 Select / Radio 等组件渲染。

#### 响应示例 JSON
```json
{
    "code": 200,
    "msg": "查询成功",
    "data": {
        "payTypeOptions": [
            {
                "label": "招生0元课",
                "value": 1
            },
            {
                "label": "进阶课",
                "value": 2
            },
            {
                "label": "高阶课",
                "value": 3
            }
        ],
        "playTypeOptions": [
            {
                "label": "直播课",
                "value": 1
            },
            {
                "label": "录播课",
                "value": 2
            }
        ],
        "isFreeOptions": [
            {
                "label": "免费课",
                "value": 1
            },
            {
                "label": "付费课",
                "value": 0
            }
        ],
        "scheduleTypeOptions": [
            {
                "label": "固定日期",
                "value": 1
            },
            {
                "label": "动态解锁",
                "value": 2
            }
        ],
        "statusOptions": [
            {
                "label": "草稿",
                "value": 0
            },
            {
                "label": "上架",
                "value": 1
            },
            {
                "label": "下架",
                "value": 2
            }
        ]
    }
}
```

#### `data` 字段说明
| 字段 | 类型 | 说明 |
| --- | --- | --- |
| payTypeOptions | array | 付费类型选项 |
| playTypeOptions | array | 播放类型选项 |
| scheduleTypeOptions | array | 排课类型选项 |
| statusOptions | array | 课程状态选项 |

每个选项对象结构：

| 字段 | 类型 | 说明 |
| --- | --- | --- |
| label | string | 显示文本 |
| value | number | 枚举值 |

---

### 3.2 获取课程分页列表
- 方法：`GET`
- 路径：`/api/admin/course/list`

#### Query 参数
| 参数 | 类型 | 必填 | 说明 |
| --- | --- | --- | --- |
| pageNum | number | 否 | 页码，默认 `1` |
| pageSize | number | 否 | 每页条数，默认 `10` |
| courseTitle | string | 否 | 课程标题（模糊搜索） |
| categoryId | number | 否 | 分类 ID |
| payType | number | 否 | 付费类型：`1` 招生0元课，`2` 进阶课，`3` 高阶课 |
| playType | number | 否 | 播放类型：`1` 直播，`2` 录播，`3` 图文，`4` 音频 |
| status | number | 否 | 状态：`0` 草稿，`1` 上架，`2` 下架 |
| isFree | number | 否 | 是否免费：`0` 否，`1` 是 |
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
      "courseId": 1,
      "categoryId": 10,
      "categoryName": "健康课程",
      "courseTitle": "中医基础入门",
      "payType": 1,
      "playType": 2,
      "scheduleType": 1,
      "coverImage": "https://cdn.example.com/cover/course1.png",
      "itemImage": "https://cdn.example.com/item/course1.png",
      "originalPrice": "99.00",
      "currentPrice": "0.00",
      "isFree": 1,
      "status": 1,
      "publishTime": "2026-02-20 10:00:00",
      "createdAt": "2026-02-18 14:20:00"
    }
  ]
}
```

#### `rows` 字段说明
| 字段 | 类型 | 说明 |
| --- | --- | --- |
| courseId | number | 课程 ID |
| categoryId | number | 分类 ID |
| categoryName | string\|null | 分类名称（关联查询） |
| courseTitle | string | 课程标题 |
| payType | number | 付费类型 |
| playType | number | 播放类型 |
| scheduleType | number | 排课类型 |
| coverImage | string\|null | 封面图地址 |
| itemImage | string\|null | 详情图地址 |
| originalPrice | string | 原价 |
| currentPrice | string | 现价 |
| isFree | number | 是否免费：`0` 否，`1` 是 |
| status | number | 状态：`0` 草稿，`1` 上架，`2` 下架 |
| publishTime | string\|null | 发布时间，格式：`Y-m-d H:i:s` |
| createdAt | string\|null | 创建时间，格式：`Y-m-d H:i:s` |

---

### 3.3 获取课程下拉选项
- 方法：`GET`
- 路径：`/api/admin/course/optionselect`
- 参数：无
- 说明：仅返回上架状态的课程，用于下拉选择。

#### 响应示例 JSON
```json
{
  "code": 200,
  "msg": "查询成功",
  "data": [
    {
      "courseId": 1,
      "courseTitle": "中医基础入门"
    }
  ]
}
```

#### `data` 字段说明
| 字段 | 类型 | 说明 |
| --- | --- | --- |
| courseId | number | 课程 ID |
| courseTitle | string | 课程标题 |

---

### 3.4 获取课程详情
- 方法：`GET`
- 路径：`/api/admin/course/{courseId}`

#### Path 参数
| 参数 | 类型 | 必填 | 说明 |
| --- | --- | --- | --- |
| courseId | number | 是 | 课程 ID（正整数） |

#### 响应示例 JSON
```json
{
  "code": 200,
  "msg": "查询成功",
  "data": {
    "courseId": 1,
    "categoryId": 10,
    "courseTitle": "中医基础入门",
    "courseSubtitle": "零基础也能学的中医课",
    "payType": 1,
    "playType": 2,
    "scheduleType": 1,
    "coverImage": "https://cdn.example.com/cover/course1.png",
    "itemImage": "https://cdn.example.com/item/course1.png",
    "description": "课程详细介绍...",
    "remark": "内部备注",
    "originalPrice": "99.00",
    "currentPrice": "0.00",
    "isFree": 1,
    "status": 1,
    "publishTime": "2026-02-20 10:00:00",
    "createdAt": "2026-02-18 14:20:00",
    "updatedAt": "2026-02-19 09:00:00",
    "category": {
      "categoryId": 10,
      "parentId": 0,
      "categoryName": "健康课程"
    }
  }
}
```

#### `data` 字段说明
| 字段 | 类型 | 说明 |
| --- | --- | --- |
| courseId | number | 课程 ID |
| categoryId | number | 分类 ID |
| courseTitle | string | 课程标题 |
| courseSubtitle | string\|null | 课程副标题 |
| payType | number | 付费类型 |
| playType | number | 播放类型 |
| scheduleType | number | 排课类型 |
| coverImage | string\|null | 封面图地址 |
| itemImage | string\|null | 详情图地址 |
| description | string\|null | 课程描述 |
| remark | string\|null | 备注 |
| originalPrice | string | 原价 |
| currentPrice | string | 现价 |
| isFree | number | 是否免费 |
| status | number | 状态 |
| publishTime | string\|null | 发布时间 |
| createdAt | string\|null | 创建时间 |
| updatedAt | string\|null | 更新时间 |
| category | object\|null | 关联分类信息 |

---

### 3.5 新增课程
- 方法：`POST`
- 路径：`/api/admin/course`

#### Body 参数（JSON）
| 参数 | 类型 | 必填 | 说明 |
| --- | --- | --- | --- |
| categoryId | number | 是 | 分类 ID，`>= 1` |
| courseTitle | string | 是 | 课程标题，最长 `200` 字符 |
| courseSubtitle | string | 否 | 课程副标题，最长 `300` 字符 |
| payType | number | 是 | 付费类型：`1` 招生0元课，`2` 进阶课，`3` 高阶课 |
| playType | number | 是 | 播放类型：`1` 直播，`2` 录播，`3` 图文，`4` 音频 |
| scheduleType | number | 否 | 排课类型：`1` 固定日期，`2` 动态解锁，默认 `1` |
| coverImage | string | 否 | 封面图地址，最长 `500` 字符 |
| itemImage | string | 否 | 详情图地址，最长 `500` 字符 |
| description | string | 否 | 课程描述 |
| remark | string | 否 | 备注 |
| originalPrice | number | 否 | 原价，`>= 0`，默认 `0` |
| currentPrice | number | 否 | 现价，`>= 0`，默认 `0` |
| isFree | number | 否 | 是否免费：`0` 否，`1` 是，默认 `0` |
| status | number | 否 | 状态：`0` 草稿，`1` 上架，`2` 下架，默认 `0` |
| publishTime | string | 否 | 发布时间，日期格式 |

#### 请求示例 JSON
```json
{
  "categoryId": 10,
  "courseTitle": "中医基础入门",
  "courseSubtitle": "零基础也能学的中医课",
  "payType": 1,
  "playType": 2,
  "scheduleType": 1,
  "coverImage": "https://cdn.example.com/cover/course1.png",
  "itemImage": "https://cdn.example.com/item/course1.png",
  "description": "课程详细介绍...",
  "originalPrice": 99.00,
  "currentPrice": 0.00,
  "isFree": 1,
  "status": 0
}
```

#### 响应示例 JSON（成功）
```json
{
  "code": 200,
  "msg": "新增成功",
  "courseId": 1
}
```

#### 响应示例 JSON（失败）
```json
{
  "code": 6000,
  "msg": "课程分类不存在",
  "data": []
}
```

---

### 3.6 更新课程
- 方法：`PUT`
- 路径：`/api/admin/course`

#### Body 参数（JSON）
| 参数 | 类型 | 必填 | 说明 |
| --- | --- | --- | --- |
| courseId | number | 是 | 课程 ID，`>= 1` |
| categoryId | number | 否 | 分类 ID，`>= 1` |
| courseTitle | string | 否 | 课程标题，最长 `200` 字符 |
| courseSubtitle | string | 否 | 课程副标题，最长 `300` 字符 |
| payType | number | 否 | 付费类型：`1` 招生0元课，`2` 进阶课，`3` 高阶课 |
| playType | number | 否 | 播放类型：`1` 直播，`2` 录播，`3` 图文，`4` 音频 |
| scheduleType | number | 否 | 排课类型：`1` 固定日期，`2` 动态解锁 |
| coverImage | string | 否 | 封面图地址，最长 `500` 字符 |
| itemImage | string | 否 | 详情图地址，最长 `500` 字符 |
| description | string | 否 | 课程描述 |
| remark | string | 否 | 备注 |
| originalPrice | number | 否 | 原价，`>= 0` |
| currentPrice | number | 否 | 现价，`>= 0` |
| isFree | number | 否 | 是否免费：`0` 否，`1` 是 |
| status | number | 否 | 状态：`0` 草稿，`1` 上架，`2` 下架 |
| publishTime | string | 否 | 发布时间，日期格式 |

#### 请求示例 JSON
```json
{
  "courseId": 1,
  "courseTitle": "中医基础入门（修订版）",
  "currentPrice": 49.00,
  "isFree": 0
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
  "msg": "课程不存在",
  "data": []
}
```

---

### 3.7 修改课程状态
- 方法：`PUT`
- 路径：`/api/admin/course/changeStatus`
- 说明：上架时会自动记录 `publish_time`。

#### Body 参数（JSON）
| 参数 | 类型 | 必填 | 说明 |
| --- | --- | --- | --- |
| courseId | number | 是 | 课程 ID，`>= 1` |
| status | number | 是 | 状态：`0` 草稿，`1` 上架，`2` 下架 |

#### 请求示例 JSON
```json
{
  "courseId": 1,
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
  "msg": "课程不存在",
  "data": []
}
```

---

### 3.8 批量修改课程排序
- 方法：`PUT`
- 路径：`/api/admin/course/batchSort`

#### Body 参数（JSON）
| 参数 | 类型 | 必填 | 说明 |
| --- | --- | --- | --- |
| course | array | 是 | 课程排序数组，至少 1 项 |
| course[].courseId | number | 是 | 课程 ID，`>= 1`，且不能重复 |
| course[].courseSort | number | 是 | 排序值，`>= 0` |

#### 请求示例 JSON
```json
{
  "course": [
    {
      "courseId": 1,
      "courseSort": 999
    },
    {
      "courseId": 8,
      "courseSort": 0
    },
    {
      "courseId": 7,
      "courseSort": 0
    },
    {
      "courseId": 4,
      "courseSort": 0
    },
    {
      "courseId": 2,
      "courseSort": 0
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
  "code": 6000,
  "msg": "排序数据不能为空",
  "data": []
}
```

---

### 3.9 删除课程（支持批量）
- 方法：`DELETE`
- 路径：`/api/admin/course/{courseIds}`
- 说明：软删除。如果课程下存在章节则不允许删除。

#### Path 参数
| 参数 | 类型 | 必填 | 说明 |
| --- | --- | --- | --- |
| courseIds | string | 是 | 课程 ID 字符串；单个如 `1`，批量如 `1,2,3` |

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
  "code": 6000,
  "msg": "课程下存在章节，无法删除",
  "data": []
}
```
