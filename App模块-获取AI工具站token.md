## 获取AI工具站token

---

## 1. 获取AI工具站token

### 请求信息

- **接口地址**：`/api/app/v1/suxuedao/getAuthorization`
- **请求方式**：GET
- **接口说明**：用于获取AI工具站token。
- **是否鉴权**：是

### 请求示例

```http
GET /api/app/v1/suxuedao/getAuthorization
```

### 响应示例

```json
{
    "code": 200,
    "msg": "success",
    "data": {
        "aiToken": "00d73wGHA4qGBEx8/5azvL605DVugwfOoZQPr5ofm/apA1eBz+E93/6Scg"
    }
}
```

### 响应字段说明

顶层字段：

| 字段名     | 类型     | 说明             |
|---------|--------|----------------|
| code    | number | 状态码，`200` 表示成功 |
| message | string | 响应消息           |
| data    | object | 业务数据           |

`data` 字段：

| 字段名     | 类型     | 必返 | 说明         |
|---------|--------|----|------------|
| aiToken | string | 是  | AI工具站token |

