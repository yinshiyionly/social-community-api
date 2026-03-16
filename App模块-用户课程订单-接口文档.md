# 我的订单列表接口文档

## 1. 接口说明

- **接口地址**：`/api/app/v1/order/list`
- **请求方式**：`GET`
- **接口用途**：获取当前登录用户的订单列表（我的 -> 我的订单）

---

## 2. 请求参数

| 参数名 | 类型 | 必填 | 说明 |
| --- | --- | --- | --- |
| page | number | 是 | 页码，从 1 开始 |
| pageSize | number | 是 | 每页条数，建议 10/20 |
| status | string | 否 | 订单状态：`unpaid` / `paid` / `closed` / `refunded` |

### 请求示例

```http
GET /api/app/v1/order/list?page=1&pageSize=20
```

---

## 3. 响应数据

### 响应结构

```json
{
  "code": 200,
  "message": "success",
  "data": {
    "list": [
      {
        "orderId": "7293469236472364781",
        "title": "课程标题课程标题",
        "amount": 0.02,
        "status": "paid",
        "createTime": "2026.03.13 16:58",
        "courseId": 10001
      }
    ],
    "total": 1,
    "page": 1,
    "pageSize": 20
  }
}
```

---

## 4. 字段说明

### data.list 列表项字段

| 字段名 | 类型 | 必填 | 说明 |
| --- | --- | --- | --- |
| orderId | string | 是 | 订单号 |
| title | string | 是 | 订单标题（课程标题） |
| amount | number | 是 | 订单金额（单位：元） |
| status | string | 是 | 支付状态：`unpaid` / `paid` / `closed` / `refunded` |
| createTime | string | 是 | 下单时间，格式建议 `YYYY.MM.DD HH:mm` |
| courseId | number | 否 | 课程 ID，前端可用于跳转课程详情 |

