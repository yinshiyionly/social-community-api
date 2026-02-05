# 课程页面接口文档

## 1. 获取大咖直播列表

### 请求信息
- **接口地址**：`/api/course/live-list`
- **请求方式**：GET
- **请求参数**：无

### 响应数据

#### 成功响应
```json
{
  "code": 200,
  "message": "success",
  "data": [
    {
      "id": 1,
      "cover": "https://example.com/cover.jpg",
      "liveTime": "1月5日 19:00",
      "title": "东莞实践课堂，三维码科技课堂",
      "viewCount": 161,
      "replayUrl": "https://example.com/replay/123"
    },
    {
      "id": 2,
      "cover": "https://example.com/cover2.jpg",
      "liveTime": "1月6日 20:00",
      "title": "智慧课堂教学实践分享",
      "viewCount": 203,
      "replayUrl": ""
    }
  ]
}
```

#### 字段说明
| 字段名 | 类型 | 说明 |
|--------|------|------|
| id | number | 直播ID |
| cover | string | 封面图片URL |
| liveTime | string | 直播时间（格式：月日 时:分） |
| title | string | 直播标题 |
| viewCount | number | 观看人数 |
| replayUrl | string | 回放地址，为空表示无回放 |

---

## 2. 获取好课上新列表

### 请求信息
- **接口地址**：`/api/course/new-courses`
- **请求方式**：GET
- **请求参数**：无

### 响应数据

#### 成功响应
```json
{
  "code": 200,
  "message": "success",
  "data": [
    {
      "id": 1,
      "cover": "https://example.com/course1.jpg",
      "title": "小寒养生3步走 欧林好养生",
      "desc": "欧林国医馆本草生活馆",
      "price": "1.00",
      "originalPrice": "99.00"
    },
    {
      "id": 2,
      "cover": "https://example.com/course2.jpg",
      "title": "冬季养生指南",
      "desc": "中医养生堂",
      "price": "9.90",
      "originalPrice": "199.00"
    }
  ]
}
```

#### 字段说明
| 字段名 | 类型 | 说明 |
|--------|------|------|
| id | number | 课程ID |
| cover | string | 课程封面图片URL |
| title | string | 课程标题 |
| desc | string | 课程描述/机构名称 |
| price | string | 现价（元） |
| originalPrice | string | 原价（元） |

---

## 3. 获取名师好课列表

### 请求信息
- **接口地址**：`/api/course/teacher-courses`
- **请求方式**：GET
- **请求参数**：无

### 响应数据

#### 成功响应
```json
{
  "code": 200,
  "message": "success",
  "data": [
    {
      "id": 1,
      "cover": "https://example.com/teacher1.jpg",
      "title": "小寒养生3步走 欧林好养生",
      "desc": "欧林国医馆本草生活馆",
      "price": "1.00",
      "originalPrice": "99.00"
    },
    {
      "id": 2,
      "cover": "https://example.com/teacher2.jpg",
      "title": "传统健身养生课",
      "desc": "养生大师工作室",
      "price": "19.90",
      "originalPrice": "299.00"
    }
  ]
}
```

#### 字段说明
| 字段名 | 类型 | 说明 |
|--------|------|------|
| id | number | 课程ID |
| cover | string | 课程封面图片URL |
| title | string | 课程标题 |
| desc | string | 课程描述/机构名称 |
| price | string | 现价（元） |
| originalPrice | string | 原价（元） |

---

## 通用说明

### 错误响应
```json
{
  "code": 500,
  "message": "服务器错误",
  "data": null
}
```

### 常见错误码
| 错误码 | 说明 |
|--------|------|
| 200 | 成功 |
| 400 | 请求参数错误 |
| 401 | 未授权 |
| 404 | 资源不存在 |
| 500 | 服务器内部错误 |

### 注意事项
1. 所有接口返回的图片URL需要是完整的HTTPS地址
2. 价格字段为字符串类型，保留两位小数
3. 直播时间格式统一为"月日 时:分"（如：1月5日 19:00）
4. replayUrl 为空字符串表示无回放，前端不显示"看回放"按钮
