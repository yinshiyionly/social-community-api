# Admin 模块课程接口文档

## 1. 接口列表总览
| 功能 | 方法 | 路径 |
| --- | --- | --- |
| 课程常量选项 | GET | `/api/admin/course/constants` |

> 其他课程 CRUD 接口文档待补充

## 2. 通用说明
- 鉴权：所有接口都需要 `Authorization: Bearer {token}`
- 请求头建议：

```http
Content-Type: application/json
Accept: application/json
Authorization: Bearer {token}
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
      { "label": "招生0元课", "value": 1 },
      { "label": "进阶课", "value": 2 },
      { "label": "高阶课", "value": 3 }
    ],
    "playTypeOptions": [
      { "label": "直播课", "value": 1 },
      { "label": "录播课", "value": 2 },
      { "label": "图文课", "value": 3 },
      { "label": "音频课", "value": 4 }
    ],
    "scheduleTypeOptions": [
      { "label": "固定日期", "value": 1 },
      { "label": "动态解锁", "value": 2 }
    ],
    "statusOptions": [
      { "label": "草稿", "value": 0 },
      { "label": "上架", "value": 1 },
      { "label": "下架", "value": 2 }
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
