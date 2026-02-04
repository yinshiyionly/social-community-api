# 课程模块数据库设计文档

## 概述

课程模块包含 29 张数据表，支持在线课程的完整生命周期管理，包括课程管理、章节内容、用户学习、订单交易、营销推广等功能。

## 数据表分类

### 1. 后台管理表（含完整审计字段）

这些表由后台管理员操作，包含 `created_at`, `created_by`, `updated_at`, `updated_by`, `deleted_at`, `deleted_by` 六个审计字段。

| 表名 | 说明 | 主键 |
|-----|------|------|
| app_course_category | 课程分类表 | category_id |
| app_course_base | 课程基础表 | course_id |
| app_course_teacher | 课程讲师表 | teacher_id |
| app_course_chapter | 课程章节表 | chapter_id |
| app_coupon_template | 优惠券模板表 | coupon_id |
| app_certificate_template | 证书模板表 | template_id |

### 2. 用户行为表（Laravel 标准时间戳）

这些表记录用户行为，使用 Laravel 标准时间戳字段 `created_at` / `updated_at`，部分表支持软删除 `deleted_at`。

| 表名 | 说明 | 主键 | 软删除 |
|-----|------|------|--------|
| app_member_course | 用户课程表 | id | ✓ |
| app_member_schedule | 用户课表 | id | ✓ |
| app_member_chapter_progress | 章节学习进度表 | id | ✓ |
| app_member_homework_submit | 作业提交表 | submit_id | ✓ |
| app_member_coupon | 用户优惠券表 | id | ✓ |
| app_member_learning_note | 学习笔记表 | note_id | ✓ |
| app_member_learning_checkin | 学习打卡表 | id | - |
| app_member_certificate | 用户证书表 | cert_id | - |

### 3. 章节内容表（Laravel 标准时间戳 + 软删除）

| 表名 | 说明 | 主键 |
|-----|------|------|
| app_chapter_content_article | 图文课内容表 | id |
| app_chapter_content_video | 录播课内容表 | id |
| app_chapter_content_live | 直播课内容表 | id |
| app_chapter_content_audio | 音频课内容表 | id |
| app_chapter_homework | 章节作业配置表 | homework_id |

### 4. 订单交易表（Laravel 标准时间戳 + 软删除）

| 表名 | 说明 | 主键 | 软删除 |
|-----|------|------|--------|
| app_course_order | 课程订单表 | order_id | ✓ |
| app_course_order_pay_log | 订单支付日志表 | log_id | - |

### 5. 互动评价表（Laravel 标准时间戳 + 软删除）

| 表名 | 说明 | 主键 | 软删除 |
|-----|------|------|--------|
| app_course_comment | 课程评价表 | comment_id | ✓ |
| app_course_favorite | 课程收藏表 | id | - |
| app_course_view_log | 课程浏览记录表 | id | - |
| app_course_qa | 课程问答表 | qa_id | ✓ |

### 6. 营销推广表（Laravel 标准时间戳 + 软删除）

| 表名 | 说明 | 主键 | 软删除 |
|-----|------|------|--------|
| app_course_promotion | 课程推广配置表 | promotion_id | ✓ |
| app_course_group | 课程拼团表 | group_id | ✓ |
| app_course_group_member | 拼团成员表 | id | - |
| app_course_certificate | 课程证书配置表 | id | ✓ |

---

## 核心表结构详解

### app_course_category（课程分类表）

```sql
CREATE TABLE app_course_category (
    category_id int4 GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    parent_id int4 NOT NULL DEFAULT 0,          -- 父分类ID
    category_name varchar(50) NOT NULL,         -- 分类名称
    category_code varchar(50) NOT NULL,         -- 分类编码
    icon varchar(255),                          -- 分类图标
    cover varchar(255),                         -- 分类封面
    description varchar(500),                   -- 分类描述
    sort_order int4 NOT NULL DEFAULT 0,         -- 排序
    status int2 NOT NULL DEFAULT 1,             -- 状态：1=启用 2=禁用
    -- 审计字段
    created_at timestamp(0),
    created_by varchar(64),
    updated_at timestamp(0),
    updated_by varchar(64),
    deleted_at timestamp(0),
    deleted_by varchar(64)
);
```

**索引**：
- `uk_app_course_category_code_del (category_code, deleted_at)` - 编码唯一
- `idx_app_course_category_parent_id (parent_id)` - 父级查询
- `idx_app_course_category_status (status)` - 状态筛选

### app_course_base（课程基础表）

```sql
CREATE TABLE app_course_base (
    course_id int8 GENERATED ALWAYS AS IDENTITY (START 100000001) PRIMARY KEY,
    course_no varchar(32) NOT NULL,             -- 课程编号
    category_id int4 NOT NULL DEFAULT 0,        -- 分类ID
    course_title varchar(200) NOT NULL,         -- 课程标题
    course_subtitle varchar(300),               -- 课程副标题
    pay_type int2 NOT NULL DEFAULT 1,           -- 付费类型：1=体验课 2=小白课 3=进阶课 4=付费课
    play_type int2 NOT NULL DEFAULT 1,          -- 播放类型：1=图文课 2=录播课 3=直播课 4=音频课
    schedule_type int2 NOT NULL DEFAULT 1,      -- 排课类型：1=固定日期 2=动态解锁
    cover_image varchar(500),                   -- 封面图
    cover_video varchar(500),                   -- 封面视频
    banner_images jsonb DEFAULT '[]',           -- 轮播图列表
    intro_video varchar(500),                   -- 课程介绍视频
    brief text,                                 -- 课程简介
    description text,                           -- 课程详情（富文本）
    suitable_crowd text,                        -- 适合人群
    learn_goal text,                            -- 学习目标
    teacher_id int8,                            -- 主讲师ID
    assistant_ids jsonb DEFAULT '[]',           -- 助教ID列表
    original_price numeric(10,2) DEFAULT 0,     -- 原价
    current_price numeric(10,2) DEFAULT 0,      -- 现价
    point_price int4 DEFAULT 0,                 -- 积分价格
    is_free int2 DEFAULT 0,                     -- 是否免费
    total_chapter int4 DEFAULT 0,               -- 总章节数
    total_duration int4 DEFAULT 0,              -- 总时长（秒）
    valid_days int4 DEFAULT 0,                  -- 有效期天数（0=永久）
    allow_download int2 DEFAULT 0,              -- 允许下载
    allow_comment int2 DEFAULT 1,               -- 允许评论
    allow_share int2 DEFAULT 1,                 -- 允许分享
    enroll_count int4 DEFAULT 0,                -- 报名人数
    view_count int4 DEFAULT 0,                  -- 浏览次数
    complete_count int4 DEFAULT 0,              -- 完课人数
    comment_count int4 DEFAULT 0,               -- 评论数
    avg_rating numeric(2,1) DEFAULT 5.0,        -- 平均评分
    sort_order int4 DEFAULT 0,                  -- 排序
    is_recommend int2 DEFAULT 0,                -- 是否推荐
    is_hot int2 DEFAULT 0,                      -- 是否热门
    is_new int2 DEFAULT 0,                      -- 是否新课
    status int2 DEFAULT 0,                      -- 状态：0=草稿 1=上架 2=下架
    publish_time timestamp(0),                  -- 上架时间
    -- 审计字段
    created_at timestamp(0),
    created_by varchar(64),
    updated_at timestamp(0),
    updated_by varchar(64),
    deleted_at timestamp(0),
    deleted_by varchar(64)
);
```

**索引**：
- `uk_app_course_base_course_no_del (course_no, deleted_at)` - 编号唯一
- `idx_app_course_base_category_id (category_id)` - 分类查询
- `idx_app_course_base_teacher_id (teacher_id)` - 讲师查询
- `idx_app_course_base_status (status)` - 状态筛选
- `idx_app_course_base_list (status, is_recommend, sort_order)` - 列表排序

### app_course_teacher（课程讲师表）

```sql
CREATE TABLE app_course_teacher (
    teacher_id int8 GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    member_id int8,                             -- 关联用户ID
    teacher_name varchar(50) NOT NULL,          -- 讲师姓名
    avatar varchar(500),                        -- 头像
    title varchar(100),                         -- 头衔/职称
    brief varchar(500),                         -- 简介
    description text,                           -- 详细介绍
    tags jsonb DEFAULT '[]',                    -- 标签
    certificates jsonb DEFAULT '[]',            -- 资质证书
    course_count int4 DEFAULT 0,                -- 课程数
    student_count int4 DEFAULT 0,               -- 学员数
    avg_rating numeric(2,1) DEFAULT 5.0,        -- 平均评分
    sort_order int4 DEFAULT 0,                  -- 排序
    is_recommend int2 DEFAULT 0,                -- 是否推荐
    status int2 DEFAULT 1,                      -- 状态：1=启用 2=禁用
    -- 审计字段
    created_at timestamp(0),
    created_by varchar(64),
    updated_at timestamp(0),
    updated_by varchar(64),
    deleted_at timestamp(0),
    deleted_by varchar(64)
);
```

### app_course_chapter（课程章节表）

```sql
CREATE TABLE app_course_chapter (
    chapter_id int8 GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    course_id int8 NOT NULL,                    -- 课程ID
    chapter_no int4 DEFAULT 0,                  -- 章节序号
    chapter_title varchar(200) NOT NULL,        -- 章节标题
    chapter_subtitle varchar(300),              -- 章节副标题
    cover_image varchar(500),                   -- 章节封面
    brief text,                                 -- 章节简介
    is_free int2 DEFAULT 0,                     -- 是否免费试看
    is_preview int2 DEFAULT 0,                  -- 是否先导课
    unlock_type int2 DEFAULT 1,                 -- 解锁类型：1=立即 2=按天数 3=按日期
    unlock_days int4 DEFAULT 0,                 -- 解锁天数
    unlock_date date,                           -- 固定解锁日期
    unlock_time time,                           -- 解锁时间点
    has_homework int2 DEFAULT 0,                -- 是否有作业
    homework_required int2 DEFAULT 0,           -- 作业是否必做
    duration int4 DEFAULT 0,                    -- 时长（秒）
    min_learn_time int4 DEFAULT 0,              -- 最少学习时长
    allow_skip int2 DEFAULT 0,                  -- 允许跳过
    allow_speed int2 DEFAULT 1,                 -- 允许倍速
    view_count int4 DEFAULT 0,                  -- 观看次数
    complete_count int4 DEFAULT 0,              -- 完成人数
    homework_count int4 DEFAULT 0,              -- 作业提交数
    sort_order int4 DEFAULT 0,                  -- 排序
    status int2 DEFAULT 1,                      -- 状态：0=草稿 1=上架 2=下架
    -- 审计字段
    created_at timestamp(0),
    created_by varchar(64),
    updated_at timestamp(0),
    updated_by varchar(64),
    deleted_at timestamp(0),
    deleted_by varchar(64)
);
```

**索引**：
- `idx_app_course_chapter_course_id (course_id)` - 课程查询
- `idx_app_course_chapter_course_no (course_id, chapter_no)` - 章节序号
- `idx_app_course_chapter_list (course_id, status, sort_order)` - 列表排序

### app_chapter_content_video（录播课内容表）

```sql
CREATE TABLE app_chapter_content_video (
    id int8 GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    chapter_id int8 NOT NULL,                   -- 章节ID（唯一）
    video_url varchar(500),                     -- 视频地址
    video_id varchar(100),                      -- 视频ID（云存储）
    video_source varchar(50) DEFAULT 'local',   -- 视频来源：local/aliyun/tencent/volcengine
    duration int4 DEFAULT 0,                    -- 视频时长（秒）
    width int4 DEFAULT 0,                       -- 视频宽度
    height int4 DEFAULT 0,                      -- 视频高度
    file_size int8 DEFAULT 0,                   -- 文件大小（字节）
    cover_image varchar(500),                   -- 视频封面
    quality_list jsonb DEFAULT '[]',            -- 清晰度列表
    subtitles jsonb DEFAULT '[]',               -- 字幕列表
    attachments jsonb DEFAULT '[]',             -- 课件附件
    allow_download int2 DEFAULT 0,              -- 允许下载
    drm_enabled int2 DEFAULT 0,                 -- DRM加密
    created_at timestamp(0),
    updated_at timestamp(0),
    deleted_at timestamp(0)
);
```

**索引**：`uk_app_chapter_content_video_chapter_id (chapter_id)` - 章节唯一

### app_chapter_content_live（直播课内容表）

```sql
CREATE TABLE app_chapter_content_live (
    id int8 GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    chapter_id int8 NOT NULL,                   -- 章节ID（唯一）
    live_platform varchar(50) DEFAULT 'custom', -- 直播平台
    live_room_id varchar(100),                  -- 直播间ID
    live_push_url varchar(500),                 -- 推流地址
    live_pull_url varchar(500),                 -- 拉流地址
    live_cover varchar(500),                    -- 直播封面
    live_start_time timestamp(0),               -- 直播开始时间
    live_end_time timestamp(0),                 -- 直播结束时间
    live_duration int4 DEFAULT 0,               -- 预计时长（分钟）
    live_status int2 DEFAULT 0,                 -- 状态：0=未开始 1=直播中 2=已结束 3=已取消
    has_playback int2 DEFAULT 0,                -- 是否有回放
    playback_url varchar(500),                  -- 回放地址
    playback_duration int4 DEFAULT 0,           -- 回放时长（秒）
    allow_chat int2 DEFAULT 1,                  -- 允许聊天
    allow_gift int2 DEFAULT 0,                  -- 允许送礼
    online_count int4 DEFAULT 0,                -- 在线人数
    max_online_count int4 DEFAULT 0,            -- 最高在线
    attachments jsonb DEFAULT '[]',             -- 直播资料
    created_at timestamp(0),
    updated_at timestamp(0),
    deleted_at timestamp(0)
);
```

### app_member_course（用户课程表）

```sql
CREATE TABLE app_member_course (
    id int8 GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    member_id int8 NOT NULL,                    -- 用户ID
    course_id int8 NOT NULL,                    -- 课程ID
    order_no varchar(64),                       -- 订单号
    source_type int2 DEFAULT 1,                 -- 来源：1=购买 2=免费领取 3=兑换 4=赠送 5=活动
    paid_amount numeric(10,2) DEFAULT 0,        -- 实付金额
    paid_points int4 DEFAULT 0,                 -- 使用积分
    enroll_time timestamp(0),                   -- 报名时间
    expire_time timestamp(0),                   -- 过期时间
    is_expired int2 DEFAULT 0,                  -- 是否过期
    learned_chapters int4 DEFAULT 0,            -- 已学章节数
    total_chapters int4 DEFAULT 0,              -- 总章节数
    learned_duration int4 DEFAULT 0,            -- 已学时长（秒）
    progress numeric(5,2) DEFAULT 0,            -- 学习进度%
    last_chapter_id int8,                       -- 最后学习章节
    last_position int4 DEFAULT 0,               -- 最后播放位置（秒）
    last_learn_time timestamp(0),               -- 最后学习时间
    is_completed int2 DEFAULT 0,                -- 是否完课
    complete_time timestamp(0),                 -- 完课时间
    homework_submitted int4 DEFAULT 0,          -- 已提交作业数
    homework_total int4 DEFAULT 0,              -- 总作业数
    checkin_days int4 DEFAULT 0,                -- 打卡天数
    created_at timestamp(0),
    updated_at timestamp(0),
    deleted_at timestamp(0)
);
```

**索引**：
- `uk_app_member_course_member_course (member_id, course_id)` - 用户课程唯一
- `idx_app_member_course_recent (member_id, is_expired, last_learn_time)` - 最近学习

### app_course_order（课程订单表）

```sql
CREATE TABLE app_course_order (
    order_id int8 GENERATED ALWAYS AS IDENTITY (START 100000000001) PRIMARY KEY,
    order_no varchar(64) NOT NULL,              -- 订单号（唯一）
    member_id int8 NOT NULL,                    -- 用户ID
    course_id int8 NOT NULL,                    -- 课程ID
    course_title varchar(200) NOT NULL,         -- 课程标题（快照）
    course_cover varchar(500),                  -- 课程封面（快照）
    original_price numeric(10,2) DEFAULT 0,     -- 原价
    current_price numeric(10,2) DEFAULT 0,      -- 现价
    discount_amount numeric(10,2) DEFAULT 0,    -- 优惠金额
    coupon_amount numeric(10,2) DEFAULT 0,      -- 优惠券抵扣
    point_deduct int4 DEFAULT 0,                -- 积分抵扣数
    point_amount numeric(10,2) DEFAULT 0,       -- 积分抵扣金额
    paid_amount numeric(10,2) DEFAULT 0,        -- 实付金额
    coupon_id varchar(64),                      -- 优惠券ID
    promotion_type varchar(50),                 -- 促销类型：seckill/discount/group
    promotion_id varchar(64),                   -- 促销活动ID
    pay_status int2 DEFAULT 0,                  -- 支付状态：0=待支付 1=已支付 2=已退款 3=已关闭
    pay_type int2,                              -- 支付方式：1=微信 2=支付宝 3=余额 4=免费
    pay_trade_no varchar(100),                  -- 支付流水号
    pay_time timestamp(0),                      -- 支付时间
    expire_time timestamp(0),                   -- 订单过期时间
    refund_status int2 DEFAULT 0,               -- 退款状态
    refund_amount numeric(10,2) DEFAULT 0,      -- 退款金额
    refund_reason varchar(500),                 -- 退款原因
    refund_time timestamp(0),                   -- 退款时间
    inviter_id int8,                            -- 邀请人ID
    commission_amount numeric(10,2) DEFAULT 0,  -- 佣金金额
    commission_status int2 DEFAULT 0,           -- 佣金状态
    remark varchar(500),                        -- 备注
    client_ip varchar(50),
    user_agent varchar(500),
    created_at timestamp(0),
    updated_at timestamp(0),
    deleted_at timestamp(0)
);
```

**索引**：
- `uk_app_course_order_order_no (order_no)` - 订单号唯一
- `idx_app_course_order_member_status (member_id, pay_status)` - 用户订单查询
- `idx_app_course_order_create_time (created_at)` - 时间查询

---

## ER 关系图

```
┌─────────────────────┐
│ app_course_category │
│     (课程分类)       │
└─────────┬───────────┘
          │ 1:N
          ▼
┌─────────────────────┐      1:1      ┌─────────────────────────┐
│   app_course_base   │──────────────▶│  app_course_promotion   │
│     (课程基础)       │               │     (推广配置)           │
└─────────┬───────────┘               └─────────────────────────┘
          │
          │ 1:N
          ▼
┌─────────────────────┐      1:1      ┌─────────────────────────┐
│  app_course_chapter │──────────────▶│ app_chapter_content_*   │
│     (课程章节)       │               │ (video/article/live/    │
└─────────┬───────────┘               │  audio)                 │
          │                           └─────────────────────────┘
          │ 1:N
          ▼
┌─────────────────────┐
│ app_chapter_homework│
│     (章节作业)       │
└─────────────────────┘

┌─────────────────────┐      N:1      ┌─────────────────────────┐
│  app_course_teacher │◀─────────────│    app_course_base      │
│     (课程讲师)       │               │                         │
└─────────────────────┘               └─────────────────────────┘

┌─────────────────────┐
│   app_member_base   │
│     (用户基础)       │
└─────────┬───────────┘
          │
          │ 1:N
          ▼
┌─────────────────────┐      N:1      ┌─────────────────────────┐
│  app_member_course  │──────────────▶│    app_course_base      │
│     (用户课程)       │               │                         │
└─────────┬───────────┘               └─────────────────────────┘
          │
          │ 1:N
          ▼
┌─────────────────────────────┐
│ app_member_chapter_progress │
│       (章节学习进度)          │
└─────────────────────────────┘
```

---

## 时间戳字段规范

### 所有表统一使用 Laravel 标准时间戳

| 字段 | 类型 | 说明 |
|-----|------|------|
| created_at | timestamp(0) | 创建时间（Laravel 自动维护） |
| updated_at | timestamp(0) | 更新时间（Laravel 自动维护） |
| deleted_at | timestamp(0) | 删除时间（Laravel SoftDeletes，可选） |

### 后台管理表额外审计字段

| 字段 | 类型 | 说明 |
|-----|------|------|
| created_by | int8 | 创建人ID |
| updated_by | int8 | 更新人ID |
| deleted_by | int8 | 删除人ID |

**适用表**：app_course_category, app_course_base, app_course_teacher, app_course_chapter, app_coupon_template, app_certificate_template

**自动填充**：使用 `HasOperator` Trait 自动填充操作人ID，无需在 Service 层手动设置。

### 仅有 created_at 的表（日志/记录类）

以下表只记录创建时间，不需要更新时间：
- app_course_order_pay_log（支付日志）
- app_course_favorite（收藏记录）
- app_course_view_log（浏览记录）
- app_course_group_member（拼团成员）
- app_member_learning_checkin（学习打卡）
- app_member_certificate（用户证书）

---

## 状态枚举值

### 课程状态 (app_course_base.status)
- 0 = 草稿
- 1 = 上架
- 2 = 下架

### 章节状态 (app_course_chapter.status)
- 0 = 草稿
- 1 = 上架
- 2 = 下架

### 通用状态 (status)
- 1 = 启用
- 2 = 禁用

### 订单支付状态 (app_course_order.pay_status)
- 0 = 待支付
- 1 = 已支付
- 2 = 已退款
- 3 = 已关闭

### 直播状态 (app_chapter_content_live.live_status)
- 0 = 未开始
- 1 = 直播中
- 2 = 已结束
- 3 = 已取消

---

## 迁移文件清单

| 序号 | 文件名 | 表名 |
|-----|--------|------|
| 1 | 2026_01_29_150000_create_app_course_category_table.php | app_course_category |
| 2 | 2026_01_29_150001_create_app_course_base_table.php | app_course_base |
| 3 | 2026_01_29_150002_create_app_course_promotion_table.php | app_course_promotion |
| 4 | 2026_01_29_150003_create_app_course_teacher_table.php | app_course_teacher |
| 5 | 2026_01_29_150004_create_app_course_chapter_table.php | app_course_chapter |
| 6 | 2026_01_29_150005_create_app_chapter_content_article_table.php | app_chapter_content_article |
| 7 | 2026_01_29_150006_create_app_chapter_content_video_table.php | app_chapter_content_video |
| 8 | 2026_01_29_150007_create_app_chapter_content_live_table.php | app_chapter_content_live |
| 9 | 2026_01_29_150008_create_app_chapter_content_audio_table.php | app_chapter_content_audio |
| 10 | 2026_01_29_150009_create_app_chapter_homework_table.php | app_chapter_homework |
| 11 | 2026_01_29_150010_create_app_member_homework_submit_table.php | app_member_homework_submit |
| 12 | 2026_01_29_150011_create_app_member_course_table.php | app_member_course |
| 13 | 2026_01_29_150012_create_app_member_schedule_table.php | app_member_schedule |
| 14 | 2026_01_29_150013_create_app_member_chapter_progress_table.php | app_member_chapter_progress |
| 15 | 2026_01_29_150014_create_app_course_order_table.php | app_course_order |
| 16 | 2026_01_29_150015_create_app_course_order_pay_log_table.php | app_course_order_pay_log |
| 17 | 2026_01_29_150016_create_app_course_comment_table.php | app_course_comment |
| 18 | 2026_01_29_150017_create_app_course_favorite_table.php | app_course_favorite |
| 19 | 2026_01_29_150018_create_app_course_view_log_table.php | app_course_view_log |
| 20 | 2026_01_29_150019_create_app_course_group_table.php | app_course_group |
| 21 | 2026_01_29_150020_create_app_course_group_member_table.php | app_course_group_member |
| 22 | 2026_01_29_150021_create_app_coupon_template_table.php | app_coupon_template |
| 23 | 2026_01_29_150022_create_app_member_coupon_table.php | app_member_coupon |
| 24 | 2026_01_29_150023_create_app_member_learning_note_table.php | app_member_learning_note |
| 25 | 2026_01_29_150024_create_app_member_learning_checkin_table.php | app_member_learning_checkin |
| 26 | 2026_01_29_150025_create_app_course_qa_table.php | app_course_qa |
| 27 | 2026_01_29_150026_create_app_certificate_template_table.php | app_certificate_template |
| 28 | 2026_01_29_150027_create_app_course_certificate_table.php | app_course_certificate |
| 29 | 2026_01_29_150028_create_app_member_certificate_table.php | app_member_certificate |
