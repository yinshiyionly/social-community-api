# App 会员认证（手机号）接口文档

## 1. 接口列表总览
| 功能 | 方法 | 路径 |
| --- | --- | --- |
| 发送登录验证码 | POST | `/api/app/v1/member/sms/send` |
| 绑定手机号 | POST | `/api/app/v1/member/phone/bind` |

## 2. 通用说明
- 接口前缀：`/api/app/v1/member`
- 返回格式：统一为 `code` + `msg` + `data`
- 鉴权说明：
  - `发送登录验证码`：无需登录
  - `绑定手机号`：需要 `Authorization: Bearer {token}`
- 请求头建议：

```http
Content-Type: application/json
Accept: application/json
Authorization: Bearer {token}
```

### 2.1 通用响应示例

#### 成功响应（有业务数据）
```json
{
  "code": 200,
  "msg": "success",
  "data": {
    "expireSeconds": 300
  }
}
```

#### 成功响应（无业务数据）
```json
{
  "code": 200,
  "msg": "success",
  "data": []
}
```

#### 失败响应（业务失败）
```json
{
  "code": 600,
  "msg": "验证码错误或已过期",
  "data": []
}
```

#### 失败响应（参数校验失败）
```json
{
  "code": 400,
  "msg": "手机号格式不正确",
  "data": []
}
```

## 3. 详细接口说明

### 3.1 发送登录验证码
- 方法：`POST`
- 路径：`/api/app/v1/member/sms/send`
- 鉴权：否
- 说明：发送短信验证码。默认发送登录验证码；可通过 `scope` 指定绑定手机号验证码。

#### Body 参数
| 参数 | 类型 | 必填 | 说明 |
| --- | --- | --- | --- |
| phone | string | 是 | 手机号，正则：`^1[3-9]\d{9}$` |
| scope | string | 否 | 验证码作用域，默认 `login`。建议值：`login`（登录）、`bind_phone`（绑定手机号） |

#### 请求示例 JSON
```json
{
  "phone": "13800138000",
  "scope": "login"
}
```

#### 响应示例 JSON
```json
{
  "code": 200,
  "msg": "success",
  "data": {
    "expireSeconds": 300
  }
}
```

#### `data` 字段说明
| 字段 | 类型 | 说明 |
| --- | --- | --- |
| expireSeconds | number | 验证码有效期（秒） |

#### 常见失败场景
| code | msg 示例 | 说明 |
| --- | --- | --- |
| 400 | 手机号不能为空 / 手机号格式不正确 | 请求参数不合法 |
| 600 | 发送过于频繁，请稍后重试 | 60 秒发送间隔限制 |
| 600 | 今日发送次数已达上限，请明天再试 | 每日短信配额限制 |

---

### 3.2 绑定手机号
- 方法：`POST`
- 路径：`/api/app/v1/member/phone/bind`
- 鉴权：是（`Authorization: Bearer {token}`）
- 说明：校验绑定手机号验证码后，将手机号绑定到当前登录用户。

#### Body 参数
| 参数 | 类型 | 必填 | 说明 |
| --- | --- | --- | --- |
| phone | string | 是 | 待绑定手机号，正则：`^1[3-9]\d{9}$` |
| code | string | 是 | 4 位短信验证码 |

#### 请求示例 JSON
```json
{
  "phone": "13800138000",
  "code": "1234"
}
```

#### 响应示例 JSON
```json
{
  "code": 200,
  "msg": "success",
  "data": []
}
```

#### 常见失败场景
| code | msg 示例 | 说明 |
| --- | --- | --- |
| 400 | 手机号不能为空 / 手机号格式不正确 / 验证码格式不正确 | 请求参数不合法 |
| 401 | 请先登录 / Token无效 / 登录已过期，请重新登录 | 未登录或 token 无效 |
| 600 | 验证码错误或已过期 | 绑定验证码校验失败 |
| 600 | 当前账号已绑定手机号 | 当前用户已有手机号 |
| 600 | 该手机号已被其他账号绑定 | 目标手机号冲突 |
| 600 | 绑定失败，请稍后重试 | 绑定异常兜底错误 |
