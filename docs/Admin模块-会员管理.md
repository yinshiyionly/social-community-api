# Admin 模块会员管理接口文档

## 1. 接口列表总览

| 功能 | 方法 | 路径 |
|---|---|---|
| 会员分页列表 | GET | `/api/admin/member/list` |
| 新增官方会员 | POST | `/api/admin/member/official` |
| 更新官方会员 | PUT | `/api/admin/member/official` |

## 2. 通用说明

- 鉴权：所有接口均需通过 `system.auth` 中间件鉴权。
- 非分页响应结构：`code`、`msg`、`data`。
- 分页响应结构：`code`、`msg`、`total`、`rows`。
- 失败时返回统一错误结构，`msg` 为失败原因。

## 3. 官方会员 member_id 规则

- 官方账号使用独立低号段：`[1, 3545623190)`。
- 新增时按“当前最大 low-range member_id + 1”分配，不补洞。
- 普通注册用户继续沿用数据库序列（起始 `3545623190`），不受官方号段影响。

## 4. 详细接口说明

### 4.1 新增官方会员

- 方法：`POST`
- 路径：`/api/admin/member/official`
- 说明：创建后台官方会员账号，自动设置 `is_official=1`。

#### Body 参数（JSON）

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| nickname | string | 是 | 官方账号昵称，最长 50 字符 |
| avatar | string | 否 | 头像地址，最长 255 字符 |
| officialLabel | string | 是 | 官方标签，最长 50 字符 |
| status | number | 否 | 账号状态：`1=正常`、`2=禁用`，默认 `1` |

#### 请求示例

```json
{
    "nickname": "官方助手",
    "avatar": "app/avatar/official-helper.png",
    "officialLabel": "官方",
    "status": 1
}
```

#### 响应示例（成功）

```json
{
    "code": 200,
    "msg": "新增成功",
    "data": {
        "memberId": 1
    }
}
```

#### 失败场景

- 参数错误：返回首个参数校验错误文案。
- 官方 ID 号段耗尽：返回 `官方会员ID号段已耗尽`。
- 其他异常：返回 `操作失败，请稍后重试`。

---

### 4.2 更新官方会员（部分更新）

- 方法：`PUT`
- 路径：`/api/admin/member/official`
- 说明：仅更新显式传入字段，未传字段保持原值。

#### Body 参数（JSON）

| 参数 | 类型 | 必填 | 说明 |
|---|---|---|---|
| memberId | number | 是 | 官方会员 ID，`>=1` |
| nickname | string | 否 | 昵称，最长 50 字符 |
| avatar | string | 否 | 头像地址，最长 255 字符 |
| officialLabel | string | 否 | 官方标签，最长 50 字符 |
| status | number | 否 | 账号状态：`1=正常`、`2=禁用` |

#### 请求示例

```json
{
    "memberId": 3,
    "nickname": "平台客服",
    "officialLabel": "认证客服"
}
```

#### 响应示例（成功）

```json
{
    "code": 200,
    "msg": "修改成功",
    "data": {
        "memberId": 3
    }
}
```

#### 失败场景

- 参数错误：返回首个参数校验错误文案。
- 未传任何可更新字段：返回 `至少传入一个可更新字段`。
- 目标账号不存在或非官方账号：返回 `官方会员不存在`。
- 其他异常：返回 `操作失败，请稍后重试`。
