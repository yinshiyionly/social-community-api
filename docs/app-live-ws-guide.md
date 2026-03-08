# App 直播 WebSocket 使用文档

## 1. 功能概览

本次已实现的直播 WS 能力：

- 直播状态推送：开播 / 关播
- 红包消息推送：后台发送红包后实时通知房间内客户端
- 公开频道订阅：无需频道鉴权
- 不启用 `broadcasting/auth`（当前不走私有频道）

核心约定：

- 频道：`live.room.{roomId}`
- 事件：
  - `live.status.changed`
  - `live.red_packet.sent`

## 2. 服务端接口清单

### 2.1 App 获取直播房间信息（含 WS 配置）

- 路径：`GET /api/app/v1/live/roomInfo`
- 鉴权：需要 App JWT
- 返回新增 `data.ws` 字段（用于客户端连接 WS），示例：

```json
{
  "code": 200,
  "msg": "操作成功",
  "data": {
    "roomId": 1001,
    "thirdPartyRoomId": "26030383012944",
    "name": "小王",
    "number": 123,
    "avatar": "https://...",
    "type": 0,
    "groupId": 0,
    "ws": {
      "enabled": true,
      "broadcaster": "pusher",
      "host": "127.0.0.1",
      "port": 6001,
      "scheme": "http",
      "key": "dev-hobby-api-key",
      "channel": "live.room.1001",
      "events": {
        "statusChanged": "live.status.changed",
        "redPacketSent": "live.red_packet.sent"
      }
    }
  }
}
```

### 2.2 百家云回调入口（开播/关播）

- 路径：`POST /api/app/v1/live/callback/baijiayun`
- 鉴权：无（通过回调签名验签）
- 支持事件：`live.start`、`live.end`
- 成功响应：

```json
{
  "code": 0,
  "msg": "ok"
}
```

### 2.3 后台发送红包

- 路径：`POST /api/admin/live/room/redPacket/send`
- 鉴权：`system.auth`
- 请求体：

```json
{
  "roomId": 1001,
  "title": "直播红包",
  "content": "红包雨来啦",
  "totalAmount": 88.00,
  "packetCount": 20,
  "expireSeconds": 300,
  "extra": {
    "bizType": "live_v1"
  }
}
```

- 成功响应：

```json
{
  "code": 200,
  "msg": "发送成功",
  "data": {
    "messageId": 90001,
    "roomId": 1001,
    "event": "live.red_packet.sent",
    "createdAt": "2026-03-08 13:30:00"
  }
}
```

红包发送前置校验：

- 直播间存在
- 直播间启用（`status=1`）
- 直播中（`live_status=1`）
- 允许送礼（`allow_gift=1`）

## 3. 事件载荷定义

### 3.1 `live.status.changed`

```json
{
  "eventType": "live.started",
  "roomId": 1001,
  "liveStatus": 1,
  "actualTime": "2026-03-08 13:20:00",
  "source": "baijiayun"
}
```

说明：

- `eventType`: `live.started` / `live.ended`
- `liveStatus`: `1`=直播中，`2`=已结束

### 3.2 `live.red_packet.sent`

```json
{
  "eventType": "live.red_packet",
  "roomId": 1001,
  "messageId": 90001,
  "content": "红包雨来啦",
  "extData": {
    "title": "直播红包",
    "totalAmount": "88.00",
    "packetCount": 20,
    "expireSeconds": 300,
    "extra": {
      "bizType": "live_v1"
    }
  },
  "sender": {
    "type": "admin",
    "id": 12,
    "name": "运营A"
  },
  "createdAt": "2026-03-08 13:30:00"
}
```

## 4. 如何开启 WS 服务

## 4.1 安装依赖

> 当前实现使用 `broadcasting pusher driver`，需要安装：
> - `beyondcode/laravel-websockets`
> - `pusher/pusher-php-server`

```bash
composer update beyondcode/laravel-websockets pusher/pusher-php-server
```

## 4.2 环境变量

确保已配置（示例）：

```env
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=dev-hobby-api
PUSHER_APP_KEY=dev-hobby-api-key
PUSHER_APP_SECRET=dev-hobby-api-secret
PUSHER_HOST=127.0.0.1
PUSHER_PORT=6001
PUSHER_SCHEME=http
WEBSOCKETS_PATH=app
WEBSOCKETS_DASHBOARD_PORT=6001
```

## 4.3 数据库迁移

```bash
php artisan migrate
```

## 4.4 本地手动启动

```bash
php artisan websockets:serve
```

## 4.5 使用 Supervisor 常驻

示例配置（路径按你的机器调整）：

```ini
[program:social-community-ws]
command=/usr/local/bin/php /Users/eleven/docker-env/web/social-community-api/artisan websockets:serve
directory=/Users/eleven/docker-env/web/social-community-api
autostart=true
autorestart=true
user=www
redirect_stderr=true
stdout_logfile=/var/log/supervisor/social-community-ws.log
stopwaitsecs=10
```

常用命令：

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start social-community-ws
sudo supervisorctl status social-community-ws
```

## 5. 客户端连接与订阅

## 5.1 连接参数来源

客户端先调 `roomInfo`，读取返回的 `data.ws`：

- `host/port/scheme/key`
- `channel`
- `events`

## 5.2 JS 示例（Laravel Echo）

```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const ws = roomInfo.data.ws;

const echo = new Echo({
  broadcaster: 'pusher',
  key: ws.key,
  wsHost: ws.host,
  wsPort: ws.port,
  wssPort: ws.port,
  forceTLS: ws.scheme === 'https',
  enabledTransports: ['ws', 'wss']
});

const channel = echo.channel(ws.channel);

channel.listen('.live.status.changed', (payload) => {
  console.log('status changed', payload);
});

channel.listen('.live.red_packet.sent', (payload) => {
  console.log('red packet', payload);
});
```

## 6. 数据落库行为

### 6.1 开播/关播回调

- 更新 `app_live_room`：
  - `live_status`
  - `actual_start_time` / `actual_end_time`
- 同步更新 `app_chapter_content_live.live_status`
  - 兼容 `room_id` 与历史 `live_room_id`
- 同状态重复回调：跳过（幂等）

### 6.2 红包发送

- 写入 `app_live_chat_message`：
  - `message_type=5`（红包）
  - `member_id=0`
  - `member_name=后台操作人昵称/用户名`
- 更新 `app_live_room_stat`：
  - `message_count + 1`
  - `gift_count + packetCount`
  - `gift_amount + totalAmount`

## 7. 联调排查清单

- `supervisorctl status` 显示 WS 进程 `RUNNING`
- 服务器端口（默认 6001）可访问
- `PUSHER_*` 与客户端连接参数一致
- 客户端已订阅 `live.room.{roomId}` 且监听事件名带前缀点：`.live.status.changed` / `.live.red_packet.sent`
- 确认前端没有再调用 `POST /api/app/broadcasting/auth`（公开频道不需要）

## 8. 注意事项

- 当前红包仅“发送消息 + 推送 + 统计”，未实现领取/拆分/结算。
- 当前实现依赖 `pusher` 广播协议。即使 WS 服务由本机 `supervisor` 托管，也仍需要 `pusher/pusher-php-server` 作为 Laravel 服务端广播客户端。
- 公开频道意味着任意客户端只要知道 `roomId` 即可订阅房间事件。如果后续有安全要求，建议切回私有频道并开启鉴权。
