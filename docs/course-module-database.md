# 课程模块数据表设计文档

## 一、数据表总览

### 核心业务表（14张）

| 序号 | 表名 | 说明 | 主键 | 主键起始值 |
|-----|------|------|------|-----------|
| 1 | app_course_category | 课程分类表 | category_id | 1 |
| 2 | app_course_base | 课程基础表 | course_id | 100000001 |
| 3 | app_course_promotion | 课程推广配置表 | promotion_id | 1 |
| 4 | app_course_teacher | 课程讲师表 | teacher_id | 1 |
| 5 | app_course_chapter | 课程章节表 | chapter_id | 1 |
| 6 | app_chapter_content_article | 图文课内容表 | id | 1 |
| 7 | app_chapter_content_video | 录播课内容表 | id | 1 |
| 8 | app_chapter_content_live | 直播课内容表 | id | 1 |
| 9 | app_chapter_content_audio | 音频课内容表 | id | 1 |
| 10 | app_chapter_homework | 章节作业配置表 | homework_id | 1 |
| 11 | app_course_order | 课程订单表 | order_id | 100000000001 |
| 12 | app_course_comment | 课程评价表 | comment_id | 1 |
| 13 | app_course_group | 课程拼团表 | group_id | 1 |
| 14 | app_coupon_template | 优惠券模板表 | coupon_id | 1 |

### 用户关联表（11张）

| 序号 | 表名 | 说明 | 主键 |
|-----|------|------|------|
| 1 | app_member_course | 用户课程表 | id |
| 2 | app_member_schedule | 用户课表（解锁计划） | id |
| 3 | app_member_chapter_progress | 用户章节学习进度表 | id |
| 4 | app_member_homework_submit | 用户作业提交表 | submit_id |
| 5 | app_member_coupon | 用户优惠券表 | id |
| 6 | app_member_learning_note | 学习笔记表 | note_id |
| 7 | app_member_learning_checkin | 学习打卡记录表 | id |
| 8 | app_member_certificate | 用户证书表 | cert_id |
| 9 | app_course_group_member | 拼团成员表 | id |
| 10 | app_course_favorite | 课程收藏表 | id |
| 11 | app_course_view_log | 课程浏览记录表 | id |

### 配置/日志表（4张）

| 序号 | 表名 | 说明 | 主键 |
|-----|------|------|------|
| 1 | app_certificate_template | 证书模板表 | template_id |
| 2 | app_course_certificate | 课程证书配置表 | id |
| 3 | app_course_order_pay_log | 订单支付日志表 | log_id |
| 4 | app_course_qa | 课程问答表 | qa_id |

---

## 二、核心枚举值定义

### 2.1 课程类型枚举

#### 付费类型 (pay_type)
| 值 | 说明 | 特点 |
|---|------|-----|
| 1 | 体验课 | 免费或低价，用于引流 |
| 2 | 小白课 | 入门级，价格适中 |
| 3 | 进阶课 | 中高级，价格较高 |
| 4 | 付费课 | 正价课程 |

#### 播放类型 (play_type)
| 值 | 说明 | 内容表 |
|---|------|-------|
| 1 | 图文课 | app_chapter_content_article |
| 2 | 录播课 | app_chapter_content_video |
| 3 | 直播课 | app_chapter_content_live |
| 4 | 音频课 | app_chapter_content_audio |

#### 排课类型 (schedule_type)
| 值 | 说明 | 解锁逻辑 |
|---|------|---------|
| 1 | 固定日期 | 章节按固定日期解锁，所有用户相同 |
| 2 | 动态解锁 | 根据用户领取/购买日期 + 解锁天数计算 |

### 2.2 章节解锁类型 (unlock_type)
| 值 | 说明 | 配合字段 |
|---|------|---------|
| 1 | 立即解锁 | 购买后立即可看 |
| 2 | 按天数解锁 | unlock_days |
| 3 | 按日期解锁 | unlock_date + unlock_time |

### 2.3 订单状态

#### 支付状态 (pay_status)
| 值 | 说明 |
|---|------|
| 0 | 待支付 |
| 1 | 已支付 |
| 2 | 已退款 |
| 3 | 已关闭 |

#### 退款状态 (refund_status)
| 值 | 说明 |
|---|------|
| 0 | 无退款 |
| 1 | 申请中 |
| 2 | 已退款 |
| 3 | 已拒绝 |

### 2.4 作业类型 (homework_type)
| 值 | 说明 |
|---|------|
| 1 | 图文打卡 |
| 2 | 视频打卡 |
| 3 | 问答 |
| 4 | 文件提交 |

### 2.5 优惠券类型 (coupon_type)
| 值 | 说明 |
|---|------|
| 1 | 满减券 |
| 2 | 折扣券 |
| 3 | 无门槛券 |

---

## 三、表结构详细设计

### 3.1 app_course_category（课程分类表）

```sql
CREATE TABLE app_course_category (
    category_id int4 NOT NULL GENERATED ALWAYS AS IDENTITY,
    parent_id int4 NOT NULL DEFAULT 0,           -- 父分类ID
    category_name varchar(50) NOT NULL DEFAULT '',-- 分类名称
    category_code varchar(50) NOT NULL DEFAULT '',-- 分类编码
    icon varchar(255) NULL,                       -- 分类图标
    cover varchar(255) NULL,                      -- 分类封面
    description varchar(500) NULL,                -- 分类描述
    sort_order int4 NOT NULL DEFAULT 0,           -- 排序
    status int2 NOT NULL DEFAULT 1,               -- 状态：1=启用 2=禁用
    create_by varchar(64) NULL,
    create_time timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP,
    update_by varchar(64) NULL,
    update_time timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP,
    del_flag int2 NOT NULL DEFAULT 0,             -- 删除标志
    PRIMARY KEY (category_id)
);

-- 索引
CREATE UNIQUE INDEX uk_app_course_category_code_del ON app_course_category (category_code, del_flag);
CREATE INDEX idx_app_course_category_parent_id ON app_course_category (parent_id);
CREATE INDEX idx_app_course_category_status ON app_course_category (status);
```

### 3.2 app_course_base（课程基础表）

```sql
CREATE TABLE app_course_base (
    course_id int8 NOT NULL GENERATED ALWAYS AS IDENTITY (START 100000001),
    course_no varchar(32) NOT NULL DEFAULT '',    -- 课程编号
    category_id int4 NOT NULL DEFAULT 0,          -- 分类ID
    course_title varchar(200) NOT NULL DEFAULT '',-- 课程标题
    course_subtitle varchar(300) NULL,            -- 课程副标题
    pay_type int2 NOT NULL DEFAULT 1,             -- 付费类型
    play_type int2 NOT NULL DEFAULT 1,            -- 播放类型
    schedule_type int2 NOT NULL DEFAULT 1,        -- 排课类型
    cover_image varchar(500) NULL,                -- 封面图
    cover_video varchar(500) NULL,                -- 封面视频
    banner_images jsonb NOT NULL DEFAULT '[]',    -- 轮播图列表
    intro_video varchar(500) NULL,                -- 课程介绍视频
    brief text NULL,                              -- 课程简介
    description text NULL,                        -- 课程详情（富文本）
    suitable_crowd text NULL,                     -- 适合人群
    learn_goal text NULL,                         -- 学习目标
    teacher_id int8 NULL,                         -- 主讲师ID
    assistant_ids jsonb NOT NULL DEFAULT '[]',    -- 助教ID列表
    original_price numeric(10,2) NOT NULL DEFAULT 0,-- 原价
    current_price numeric(10,2) NOT NULL DEFAULT 0, -- 现价
    point_price int4 NOT NULL DEFAULT 0,          -- 积分价格
    is_free int2 NOT NULL DEFAULT 0,              -- 是否免费
    total_chapter int4 NOT NULL DEFAULT 0,        -- 总章节数
    total_duration int4 NOT NULL DEFAULT 0,       -- 总时长（秒）
    valid_days int4 NOT NULL DEFAULT 0,           -- 有效期天数（0=永久）
    allow_download int2 NOT NULL DEFAULT 0,       -- 允许下载
    allow_comment int2 NOT NULL DEFAULT 1,        -- 允许评论
    allow_share int2 NOT NULL DEFAULT 1,          -- 允许分享
    enroll_count int4 NOT NULL DEFAULT 0,         -- 报名人数
    view_count int4 NOT NULL DEFAULT 0,           -- 浏览次数
    complete_count int4 NOT NULL DEFAULT 0,       -- 完课人数
    comment_count int4 NOT NULL DEFAULT 0,        -- 评论数
    avg_rating numeric(2,1) NOT NULL DEFAULT 5.0, -- 平均评分
    sort_order int4 NOT NULL DEFAULT 0,           -- 排序
    is_recommend int2 NOT NULL DEFAULT 0,         -- 是否推荐
    is_hot int2 NOT NULL DEFAULT 0,               -- 是否热门
    is_new int2 NOT NULL DEFAULT 0,               -- 是否新课
    status int2 NOT NULL DEFAULT 0,               -- 状态：0=草稿 1=上架 2=下架
    publish_time timestamp(0) NULL,               -- 上架时间
    create_by varchar(64) NULL,
    create_time timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP,
    update_by varchar(64) NULL,
    update_time timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP,
    del_flag int2 NOT NULL DEFAULT 0,
    PRIMARY KEY (course_id)
);

-- 索引
CREATE UNIQUE INDEX uk_app_course_base_course_no_del ON app_course_base (course_no, del_flag);
CREATE INDEX idx_app_course_base_category_id ON app_course_base (category_id);
CREATE INDEX idx_app_course_base_pay_type ON app_course_base (pay_type);
CREATE INDEX idx_app_course_base_play_type ON app_course_base (play_type);
CREATE INDEX idx_app_course_base_teacher_id ON app_course_base (teacher_id);
CREATE INDEX idx_app_course_base_status ON app_course_base (status);
CREATE INDEX idx_app_course_base_list ON app_course_base (status, is_recommend, sort_order);
```

### 3.3 app_course_chapter（课程章节表）

```sql
CREATE TABLE app_course_chapter (
    chapter_id int8 NOT NULL GENERATED ALWAYS AS IDENTITY,
    course_id int8 NOT NULL,                      -- 课程ID
    chapter_no int4 NOT NULL DEFAULT 0,           -- 章节序号
    chapter_title varchar(200) NOT NULL DEFAULT '',-- 章节标题
    chapter_subtitle varchar(300) NULL,           -- 章节副标题
    cover_image varchar(500) NULL,                -- 章节封面
    brief text NULL,                              -- 章节简介
    is_free int2 NOT NULL DEFAULT 0,              -- 是否免费试看
    is_preview int2 NOT NULL DEFAULT 0,           -- 是否先导课
    unlock_type int2 NOT NULL DEFAULT 1,          -- 解锁类型
    unlock_days int4 NOT NULL DEFAULT 0,          -- 解锁天数
    unlock_date date NULL,                        -- 固定解锁日期
    unlock_time time NULL,                        -- 解锁时间点
    has_homework int2 NOT NULL DEFAULT 0,         -- 是否有作业
    homework_required int2 NOT NULL DEFAULT 0,    -- 作业是否必做
    duration int4 NOT NULL DEFAULT 0,             -- 时长（秒）
    min_learn_time int4 NOT NULL DEFAULT 0,       -- 最少学习时长
    allow_skip int2 NOT NULL DEFAULT 0,           -- 允许跳过
    allow_speed int2 NOT NULL DEFAULT 1,          -- 允许倍速
    view_count int4 NOT NULL DEFAULT 0,           -- 观看次数
    complete_count int4 NOT NULL DEFAULT 0,       -- 完成人数
    homework_count int4 NOT NULL DEFAULT 0,       -- 作业提交数
    sort_order int4 NOT NULL DEFAULT 0,           -- 排序
    status int2 NOT NULL DEFAULT 1,               -- 状态
    create_by varchar(64) NULL,
    create_time timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP,
    update_by varchar(64) NULL,
    update_time timestamp(0) NULL DEFAULT CURRENT_TIMESTAMP,
    del_flag int2 NOT NULL DEFAULT 0,
    PRIMARY KEY (chapter_id)
);

-- 索引
CREATE INDEX idx_app_course_chapter_course_id ON app_course_chapter (course_id);
CREATE INDEX idx_app_course_chapter_course_no ON app_course_chapter (course_id, chapter_no);
CREATE INDEX idx_app_course_chapter_list ON app_course_chapter (course_id, status, sort_order);
```

### 3.4 章节内容分表设计

#### 为什么要分表？

不同播放类型的章节，字段差异很大：
- **图文课**：content_html, images, word_count, read_time
- **录播课**：video_url, duration, quality_list, subtitles, drm_enabled
- **直播课**：live_room_id, push_url, pull_url, live_status, playback_url
- **音频课**：audio_url, transcript, timeline_text, background_play

分表优势：
1. 避免大量 NULL 字段
2. 代码中无需 if-else 判断字段
3. 各类型可独立扩展字段
4. 查询性能更好

#### 使用方式

```php
// 获取章节内容
$chapter = AppCourseChapter::find($chapterId);
$course = $chapter->course;

switch ($course->play_type) {
    case 1: // 图文课
        $content = AppChapterContentArticle::where('chapter_id', $chapterId)->first();
        break;
    case 2: // 录播课
        $content = AppChapterContentVideo::where('chapter_id', $chapterId)->first();
        break;
    case 3: // 直播课
        $content = AppChapterContentLive::where('chapter_id', $chapterId)->first();
        break;
    case 4: // 音频课
        $content = AppChapterContentAudio::where('chapter_id', $chapterId)->first();
        break;
}
```

---

## 四、ER 关系图

```
app_course_category (1) ──< (N) app_course_base
app_course_teacher (1) ──< (N) app_course_base
app_course_base (1) ──── (1) app_course_promotion
app_course_base (1) ──< (N) app_course_chapter
app_course_chapter (1) ──── (1) app_chapter_content_*
app_course_chapter (1) ──< (N) app_chapter_homework
app_member_base (1) ──< (N) app_member_course
app_member_course (1) ──< (N) app_member_schedule
app_member_base (1) ──< (N) app_course_order
app_course_base (1) ──< (N) app_course_comment
app_course_base (1) ──< (N) app_course_group
app_coupon_template (1) ──< (N) app_member_coupon
```

---

## 五、索引设计要点

### 5.1 索引命名规范

所有索引必须包含完整表名，避免 PostgreSQL 全局索引名冲突：

```sql
-- ✅ 正确
CREATE INDEX idx_app_course_base_category_id ON app_course_base (category_id);
CREATE UNIQUE INDEX uk_app_course_base_course_no_del ON app_course_base (course_no, del_flag);

-- ❌ 错误
CREATE INDEX idx_category_id ON app_course_base (category_id);
```

### 5.2 核心查询索引

| 查询场景 | 索引 |
|---------|------|
| 课程列表 | `idx_app_course_base_list (status, is_recommend, sort_order)` |
| 用户课程 | `idx_app_member_course_recent (member_id, is_expired, last_learn_time)` |
| 章节列表 | `idx_app_course_chapter_list (course_id, status, sort_order)` |
| 订单查询 | `idx_app_course_order_member_status (member_id, pay_status)` |

---

## 六、开发注意事项

### 6.1 主键使用 IDENTITY

所有表主键使用 PostgreSQL IDENTITY 列，禁止手动指定主键值：

```php
// ✅ 正确
AppCourseBase::create([
    'course_title' => '测试课程',
    'category_id' => 1,
]);

// ❌ 错误 - 会报错
AppCourseBase::create([
    'course_id' => 999,
    'course_title' => '测试课程',
]);
```

### 6.2 软删除字段

使用 `del_flag` 字段实现软删除，而非 Laravel 默认的 `deleted_at`：

```php
// Model 中配置
class AppCourseBase extends Model
{
    // 不使用 SoftDeletes trait
    
    // 查询时过滤已删除
    public function scopeActive($query)
    {
        return $query->where('del_flag', 0);
    }
    
    // 软删除
    public function softDelete()
    {
        $this->del_flag = 1;
        $this->save();
    }
}
```

### 6.3 JSON 字段处理

JSONB 字段在 Model 中配置 cast：

```php
protected $casts = [
    'banner_images' => 'array',
    'assistant_ids' => 'array',
    'template_config' => 'array',
];
```

### 6.4 金额字段精度

金额字段使用 `numeric(10,2)`，Model 中可配置 cast：

```php
protected $casts = [
    'original_price' => 'decimal:2',
    'current_price' => 'decimal:2',
];
```

### 6.5 时间字段格式

时间字段统一使用 `timestamp(0)`，精确到秒：

```php
protected $casts = [
    'create_time' => 'datetime',
    'update_time' => 'datetime',
    'publish_time' => 'datetime',
];
```

---

## 七、迁移文件清单

| 序号 | 文件名 | 创建的表 |
|-----|-------|---------|
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

---

## 八、后续扩展建议

1. **课程系列/专栏**：多课程打包销售
2. **学习路径/学习计划**：课程组合推荐
3. **班级/训练营模式**：带班学习
4. **直播连麦/互动白板**：直播课增强
5. **AI 智能推荐**：个性化课程推荐
6. **学习数据分析报表**：学习行为分析
7. **企业版/团购版**：B端业务扩展
