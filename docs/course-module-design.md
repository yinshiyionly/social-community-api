# 在线教育课程模块数据表设计

## 一、表结构概览

### 核心表
| 表名 | 说明 | 主键 |
|-----|------|-----|
| app_course_category | 课程分类表 | category_id |
| app_course_base | 课程基础表 | course_id |
| app_course_promotion | 课程推广配置表 | promotion_id |
| app_course_teacher | 课程讲师表 | teacher_id |
| app_course_chapter | 课程章节基础表 | chapter_id |

### 章节内容分表（按播放类型）
| 表名 | 说明 | 关联 |
|-----|------|-----|
| app_chapter_content_article | 图文课内容 | chapter_id |
| app_chapter_content_video | 录播课内容 | chapter_id |
| app_chapter_content_live | 直播课内容 | chapter_id |
| app_chapter_content_audio | 音频课内容 | chapter_id |

### 作业相关
| 表名 | 说明 |
|-----|------|
| app_chapter_homework | 章节作业配置表 |
| app_member_homework_submit | 用户作业提交表 |

### 用户学习相关
| 表名 | 说明 |
|-----|------|
| app_member_course | 用户课程表（已购买/领取） |
| app_member_schedule | 用户课表（章节解锁计划） |
| app_member_chapter_progress | 用户章节学习进度 |
| app_member_learning_note | 学习笔记表 |
| app_member_learning_checkin | 学习打卡记录表 |

### 订单与支付
| 表名 | 说明 |
|-----|------|
| app_course_order | 课程订单表 |
| app_course_order_pay_log | 订单支付日志 |

### 营销相关
| 表名 | 说明 |
|-----|------|
| app_course_group | 课程拼团表 |
| app_course_group_member | 拼团成员表 |
| app_coupon_template | 优惠券模板表 |
| app_member_coupon | 用户优惠券表 |

### 互动相关
| 表名 | 说明 |
|-----|------|
| app_course_comment | 课程评价表 |
| app_course_favorite | 课程收藏表 |
| app_course_view_log | 课程浏览记录表 |
| app_course_qa | 课程问答表 |

### 证书相关
| 表名 | 说明 |
|-----|------|
| app_certificate_template | 证书模板表 |
| app_course_certificate | 课程证书配置表 |
| app_member_certificate | 用户证书表 |

---

## 二、核心字段说明

### 课程类型枚举

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

### 章节解锁类型 (unlock_type)
| 值 | 说明 | 配合字段 |
|---|------|---------|
| 1 | 立即解锁 | 购买后立即可看 |
| 2 | 按天数解锁 | unlock_days |
| 3 | 按日期解锁 | unlock_date + unlock_time |

---

## 三、分表设计说明

### 为什么章节内容要分表？

不同播放类型的章节，字段差异很大：

**图文课** 需要：content_html, images, word_count, read_time
**录播课** 需要：video_url, duration, quality_list, subtitles, drm_enabled
**直播课** 需要：live_room_id, push_url, pull_url, live_status, playback_url
**音频课** 需要：audio_url, transcript, timeline_text, background_play

分表优势：
1. 避免大量 NULL 字段
2. 代码中无需 if-else 判断字段
3. 各类型可独立扩展字段
4. 查询性能更好

### 使用方式

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

## 四、章节解锁逻辑

### 固定日期模式 (schedule_type = 1)

章节设置固定的 unlock_date，所有用户在该日期解锁。

```php
// 判断是否解锁
$isUnlocked = $chapter->unlock_date <= now()->toDateString();
```

### 动态解锁模式 (schedule_type = 2)

根据用户购买日期 + 章节的 unlock_days 计算解锁日期。

```php
// 用户购买课程时，生成课表
$enrollDate = $memberCourse->enroll_time->toDateString();

foreach ($chapters as $chapter) {
    $scheduleDate = Carbon::parse($enrollDate)->addDays($chapter->unlock_days);
    
    AppMemberSchedule::create([
        'member_id' => $memberId,
        'course_id' => $courseId,
        'chapter_id' => $chapter->chapter_id,
        'schedule_date' => $scheduleDate,
        'is_unlocked' => $scheduleDate <= now()->toDateString() ? 1 : 0,
    ]);
}
```

### 先导课与免费试看

- `is_preview = 1`：先导课，无需购买即可观看
- `is_free = 1`：免费试看章节，用于引流

---

## 五、推广配置说明

### 秒杀配置
```json
{
  "seckill_enabled": 1,
  "seckill_price": 9.9,
  "seckill_start_time": "2026-02-01 10:00:00",
  "seckill_end_time": "2026-02-01 12:00:00",
  "seckill_stock": 100
}
```

### 倒计时配置
```json
{
  "countdown_enabled": 1,
  "countdown_type": 2,
  "countdown_hours": 24,
  "countdown_text": "限时优惠，仅剩"
}
```
- type=1：固定结束时间
- type=2：用户访问后 N 小时（需前端配合 localStorage）

### 虚假数据配置
```json
{
  "fake_data_enabled": 1,
  "fake_enroll_base": 1000,
  "fake_enroll_increment": 50,
  "fake_recent_buyers": [
    {"avatar": "xxx", "nickname": "小*", "time": "3分钟前"},
    {"avatar": "xxx", "nickname": "大*", "time": "5分钟前"}
  ]
}
```

---

## 六、作业打卡流程

1. 后台配置章节作业 (app_chapter_homework)
2. 用户学习章节后提交作业 (app_member_homework_submit)
3. 讲师/助教批改作业
4. 批改通过后发放积分奖励

### 作业类型
| 值 | 说明 |
|---|------|
| 1 | 图文打卡 |
| 2 | 视频打卡 |
| 3 | 问答 |
| 4 | 文件提交 |

---

## 七、证书发放流程

1. 配置证书模板 (app_certificate_template)
2. 课程关联证书配置 (app_course_certificate)
3. 用户满足条件后自动/手动发放 (app_member_certificate)

### 发放条件
| 值 | 说明 |
|---|------|
| 1 | 完课即发 |
| 2 | 完课 + 作业完成 |
| 3 | 手动发放 |

---

## 八、ER 关系图

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
```

---

## 九、索引设计要点

1. 课程列表查询：`idx_course_list (status, is_recommend, sort_order)`
2. 用户课程查询：`idx_mc_recent (member_id, is_expired, last_learn_time)`
3. 章节列表查询：`idx_chapter_list (course_id, status, sort_order)`
4. 订单查询：`idx_order_member_status (member_id, pay_status)`

---

## 十、扩展建议

### 后续可扩展功能
1. 课程系列/专栏（多课程打包）
2. 学习路径/学习计划
3. 班级/训练营模式
4. 直播连麦/互动白板
5. AI 智能推荐
6. 学习数据分析报表
7. 企业版/团购版
