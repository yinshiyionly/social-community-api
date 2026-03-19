<?php

namespace App\Services\App;

use App\Models\App\AppCourseBase;
use App\Models\App\AppCourseCategory;
use App\Models\App\AppCourseChapter;
use App\Models\App\AppCourseOrder;
use App\Models\App\AppChapterContentVideo;
use App\Models\App\AppLivePlayback;
use App\Models\App\AppLiveRoom;
use App\Models\App\AppLiveRoomReserve;
use App\Models\App\AppMemberBase;
use App\Models\App\AppMemberCourse;
use App\Services\App\LearningCenterService;
use App\Services\AppFileUploadService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * App 端课程服务。
 *
 * 职责：
 * 1. 提供课程分类、课程列表、课程详情等查询能力；
 * 2. 提供课程领取与下单前的数据准备；
 * 3. 聚合课程页大咖直播卡片数据并统一字段口径。
 */
class CourseService
{
    /**
     * 获取课程分类列表
     *
     * @return Collection
     */
    public function getCategories($limit = 0): Collection
    {
        if (!empty($limit)) {
            return AppCourseCategory::query()
                ->select(['category_id', 'category_name', 'icon'])
                ->enabled()
                ->topLevel()
                ->orderByDesc('sort_order')
                ->limit($limit)
                ->get();
        }
        return AppCourseCategory::query()
            ->select(['category_id', 'category_name', 'icon'])
            ->enabled()
            ->topLevel()
            ->orderByDesc('sort_order')
            ->get();
    }

    /**
     * 获取选课中心课程列表（按付费类型分组）
     *
     * @param int $categoryId
     * @return array
     */
    /**
     * 获取选课中心课程列表（按付费类型分组）
     *
     * @param int $categoryId
     * @return array
     */
    public function getCoursesByCategory(int $categoryId): array
    {
        $courses = AppCourseBase::query()
            ->select([
                'course_id',
                'course_title',
                'course_subtitle',
                'cover_image',
                'current_price',
                'original_price',
                'pay_type',
            ])
            ->online()
            ->where('category_id', $categoryId)
            ->orderByDesc('sort_order')
            ->get();

        // 按付费类型分组
        $grouped = $courses->groupBy('pay_type');

        $result = [];
        foreach (AppCourseBase::PAY_TYPE_CONFIG as $payType => $config) {
            if ($grouped->has($payType)) {
                // 为课程付费类型 icon 追加 CDN 域名
                $config['typeIcon'] = (new AppFileUploadService())->generateFileUrl($config['typeIcon']);
                $result[] = array_merge($config, [
                    'list' => $grouped->get($payType)->values(),
                ]);
            }
        }

        return $result;
    }

    /**
     * 获取课程详情（简单版，用于领取/购买校验）
     *
     * @param int $courseId
     * @return AppCourseBase|null
     */
    public function getCourseDetail(int $courseId): ?AppCourseBase
    {
        return AppCourseBase::query()
            ->select([
                'course_id',
                'course_title',
                'cover_image',
                'current_price',
                'original_price',
                'is_free',
                'valid_days',
                'total_chapter',
                'class_teacher_qr'
            ])
            ->online()
            ->where('course_id', $courseId)
            ->first();
    }

    /**
     * 获取入学信息详情
     *
     * @param int $memberId
     * @param int $courseId
     * @return array|null
     */
    public function getEnrollmentDetail(int $memberId, int $courseId): ?array
    {
        $course = $this->getCourseDetail($courseId);
        if (!$course) {
            return null;
        }

        $phone = AppMemberBase::query()
            ->where('member_id', $memberId)
            ->value('phone');

        $price = $course->current_price;
        $isFree = (int)$course->is_free === 1 || (float)$price <= 0;

        return [
            'phone'          => (string)($phone ?? ''),
            'payAmount'      => $isFree ? '' : number_format((float)$price, 2, '.', ''),
            'classTeacherQr' => (string)$course->class_teacher_qr ?? null
        ];
    }

    /**
     * 获取课程完整详情
     *
     * @param int $courseId
     * @return AppCourseBase|null
     */
    public function getCourseFullDetail(int $courseId): ?AppCourseBase
    {
        $course = AppCourseBase::query()
            ->with([
                'category:category_id,category_name',
            ])
            ->online()
            ->where('course_id', $courseId)
            ->first();

        if ($course) {
            // 增加浏览次数
            AppCourseBase::where('course_id', $courseId)->increment('view_count');
        }

        return $course;
    }

    /**
     * 判断课程是否存在在线章节。
     *
     * 判定规则：
     * 1. 课程必须存在且处于上架状态；
     * 2. 章节必须满足 status=online 且未软删；
     * 3. 课程不存在时返回 null，供控制器统一返回 dataNotFound。
     *
     * @param int $courseId
     * @return bool|null
     */
    public function hasOnlineChapters(int $courseId): ?bool
    {
        $courseExists = AppCourseBase::query()
            ->online()
            ->where('course_id', $courseId)
            ->exists();

        if (!$courseExists) {
            return null;
        }

        return AppCourseChapter::query()
            ->online()
            ->where('course_id', $courseId)
            ->exists();
    }

    /**
     * 获取无章节课程详情（旧版长图详情）。
     *
     * 关键规则：
     * 1. contentImage 兜底顺序：item_image -> banner_images[0] -> cover_image；
     * 2. 金额与按钮字段口径与课程详情旧版保持一致；
     * 3. 每次查询成功后累加 view_count。
     *
     * @param int $courseId
     * @return array<string, mixed>|null
     */
    public function getLegacyDetailData(int $courseId, int $memberId = 0): ?array
    {
        $course = AppCourseBase::query()
            ->select([
                'course_id',
                'item_image',
                'cover_image',
                'class_teacher_name',
                'class_teacher_qr',
                'teacher_name',
                'current_price',
                'original_price',
                'is_free',
            ])
            ->online()
            ->where('course_id', $courseId)
            ->first();

        if (!$course) {
            return null;
        }

        // 详情接口访问成功后再累计浏览次数，避免无效 ID 干扰统计。
        AppCourseBase::where('course_id', $courseId)->increment('view_count');

        $isUnlocked = $memberId > 0 && AppMemberCourse::hasCourse($memberId, $courseId);


        $bannerImages = is_array($course->banner_images) ? $course->banner_images : [];
        $contentImage = $course->item_image ?: ($bannerImages[0] ?? $course->cover_image ?? '');

        return array_merge([
            // 'contentImage' => (string)($contentImage ?? ''),
            'contentImage'     => (string)($course->item_image ?? ''),
            'coverImage'       => (string)($course->cover_image ?? ''),
            'classTeacherQr'   => (string)($course->class_teacher_qr ?? ''),
            'classTeacherName' => (string)($course->class_teacher_name ?? ''),
            'teacherName'      => (string)($course->teacher_name ?? ''),
            'isOwn'            => $isUnlocked
        ], $this->buildBottomActionData($course, false));
    }

    /**
     * 获取有章节课程详情（章节版）。
     *
     * 关键规则：
     * 1. 章节仅返回在线且未软删数据，并按 sort_order/chapter_no/chapter_id 升序；
     * 2. isUnlocked 仅由“是否拥有课程”决定；
     * 3. 已解锁课程按钮固定返回去学习（learn）；
     * 4. 仅录播课（play_type=2）查询章节 video_url 并输出 videoUrl；
     * 5. 查询成功后累加 view_count。
     *
     * @param int $courseId
     * @param int $memberId
     * @return array<string, mixed>|null
     */
    public function getChapterDetailData(int $courseId, int $memberId = 0): ?array
    {
        $course = AppCourseBase::query()
            ->select([
                'course_id',
                'course_title',
                'play_type',
                'item_image',
                'class_teacher_name',
                'class_teacher_qr',
                'cover_image',
                'teacher_name',
                'brief',
                'description',
                'enroll_count',
                'current_price',
                'original_price',
                'is_free',
            ])
            ->online()
            ->where('course_id', $courseId)
            ->first();

        if (!$course) {
            return null;
        }

        // 详情接口访问成功后再累计浏览次数，避免无效 ID 干扰统计。
        AppCourseBase::where('course_id', $courseId)->increment('view_count');

        $chapters = AppCourseChapter::query()
            ->select([
                'chapter_id',
                'course_id',
                'chapter_no',
                'chapter_title',
                'is_free',
                'is_preview',
                'unlock_date',
                'chapter_start_time',
                'duration',
                'sort_order',
            ])
            ->online()
            ->where('course_id', $courseId)
            ->orderBy('sort_order')
            ->orderBy('chapter_no')
            ->orderBy('chapter_id')
            ->get();

        $isUnlocked = $memberId > 0 && AppMemberCourse::hasCourse($memberId, $courseId);
        $chapterVideoUrlMap = [];

        // 当前仅录播课需要补充章节 videoUrl，其它播放类型统一返回空字符串。
        if ((int)$course->play_type === AppCourseBase::PLAY_TYPE_VIDEO && $chapters->isNotEmpty()) {
            $chapterVideoUrlMap = AppChapterContentVideo::query()
                ->whereIn('chapter_id', $chapters->pluck('chapter_id')->all())
                ->pluck('video_url', 'chapter_id')
                ->map(function ($videoUrl) {
                    return is_string($videoUrl) ? trim($videoUrl) : '';
                })
                ->toArray();
        }

        $chapterList = [];
        foreach ($chapters as $chapter) {
            $videoUrl = '';
            if ((int)$course->play_type === AppCourseBase::PLAY_TYPE_VIDEO) {
                $videoUrl = (string)($chapterVideoUrlMap[$chapter->chapter_id] ?? '');
            }

            $chapterList[] = [
                'id'           => (int)$chapter->chapter_id,
                'title'        => (string)$chapter->chapter_title,
                'dateText'     => $this->formatChapterDateText($chapter),
                'durationText' => $this->formatChapterDurationText((int)$chapter->duration),
                // 先导课同样允许未解锁用户播放，按免费章节口径返回 true。
                'isFree'       => (int)$chapter->is_free === 1 || (int)$chapter->is_preview === 1,
                'videoUrl'     => $videoUrl,
            ];
        }

        $data = [
            'title'            => (string)$course->course_title,
            'coverImage'       => (string)($course->cover_image ?? ''),
            'contentImage'     => (string)($course->item_image ?? ''),
            'classTeacherQr'   => (string)($course->class_teacher_qr ?? ''),
            'classTeacherName' => (string)($course->class_teacher_name ?? ''),
            'teacherName'      => (string)($course->teacher_name ?? ''),
            'studyCountText'   => $this->buildStudyCountText((int)$course->enroll_count),
            'intro'            => $this->buildCourseIntroText($course),
            'isUnlocked'       => $isUnlocked,
            'chapters'         => $chapterList,
        ];

        return array_merge($data, $this->buildBottomActionData($course, $isUnlocked));
    }

    /**
     * 检查用户是否已拥有课程
     *
     * @param int $memberId
     * @param int $courseId
     * @return bool
     */
    public function checkUserHasCourse(int $memberId, int $courseId): bool
    {
        return AppMemberCourse::hasCourse($memberId, $courseId);
    }

    /**
     * 免费领取课程
     *
     * 关键规则：
     * 1. 仅 is_free=1 或 current_price<=0 的课程允许领取，避免绕过支付链路；
     * 2. member_id + course_id 只要存在历史记录（含软删）即不允许重复领取；
     * 3. 在同一事务内同时写免费订单与用户课程，保证订单与权益一致。
     *
     * @param int $memberId
     * @param int $courseId
     * @param string $phone
     * @param string $ageRange
     * @return AppMemberCourse
     * @throws \Exception
     */
    public function claimFreeCourse(int $memberId, int $courseId, string $phone, string $ageRange): AppMemberCourse
    {
        $course = $this->getCourseDetail($courseId);

        if (!$course) {
            throw new \Exception('课程不存在');
        }

        if (!$this->isCourseFreeForClaim($course)) {
            throw new \Exception('该课程不是免费课程，请走支付下单');
        }

        return DB::transaction(function () use ($memberId, $courseId, $phone, $ageRange, $course) {
            $member = AppMemberBase::query()
                ->select(['member_id'])
                ->where('member_id', $memberId)
                ->lockForUpdate()
                ->first();

            if (!$member) {
                throw new \Exception('用户不存在');
            }

            // 只要存在历史领取/购买记录（含软删），统一视为已领取，避免重复建单和权益冲突。
            $historyCourse = AppMemberCourse::withTrashed()
                ->where('member_id', $memberId)
                ->where('course_id', $courseId)
                ->lockForUpdate()
                ->first();

            if ($historyCourse) {
                throw new \Exception('您已领取过该课程');
            }

            $enrollTime = now();
            $expireTime = null;
            if ((int)$course->valid_days > 0) {
                $expireTime = $enrollTime->copy()->addDays((int)$course->valid_days);
            }

            $order = AppCourseOrder::query()->create([
                'order_no'         => AppCourseOrder::generateOrderNo(),
                'member_id'        => $memberId,
                'course_id'        => $courseId,
                'course_title'     => (string)$course->course_title,
                'course_cover'     => (string)($course->cover_image ?? ''),
                'enroll_phone'     => $phone,
                'enroll_age_range' => $ageRange,
                // 免费领取订单金额字段统一置 0，避免与支付单口径混淆。
                'original_price'   => 0,
                'current_price'    => 0,
                'discount_amount'  => 0,
                'coupon_amount'    => 0,
                'point_deduct'     => 0,
                'point_amount'     => 0,
                'paid_amount'      => 0,
                'pay_status'       => AppCourseOrder::PAY_STATUS_PAID,
                'pay_type'         => AppCourseOrder::PAY_TYPE_FREE,
                'pay_time'         => $enrollTime,
                'refund_status'    => AppCourseOrder::REFUND_STATUS_NONE,
                'refund_amount'    => 0,
            ]);

            $memberCourse = AppMemberCourse::query()->create([
                'member_id'        => $memberId,
                'course_id'        => $courseId,
                'order_no'         => $order->order_no,
                'source_type'      => AppMemberCourse::SOURCE_TYPE_FREE,
                'enroll_phone'     => $phone,
                'enroll_age_range' => $ageRange,
                'paid_amount'      => 0,
                'enroll_time'      => $enrollTime,
                'expire_time'      => $expireTime,
                'total_chapters'   => $course->total_chapter,
            ]);

            // 更新课程报名人数
            AppCourseBase::where('course_id', $courseId)->increment('enroll_count');

            // 生成用户课表
            $learningCenterService = new LearningCenterService();
            $learningCenterService->generateSchedule(
                $memberId,
                $courseId,
                $memberCourse->id,
                $enrollTime->toDateTime()
            );

            return $memberCourse;
        });
    }

    /**
     * 判断课程是否满足免费领取资格。
     *
     * 判定规则：
     * 1. is_free=1 直接视为免费课程；
     * 2. current_price<=0 作为历史脏数据兜底，避免免费课误拦截。
     *
     * @param AppCourseBase $course
     * @return bool
     */
    protected function isCourseFreeForClaim(AppCourseBase $course): bool
    {
        if ((int)$course->is_free === 1) {
            return true;
        }

        $currentPrice = $course->current_price;
        if (is_string($currentPrice)) {
            $currentPrice = trim($currentPrice);
        }

        if ($currentPrice === '' || !is_numeric($currentPrice)) {
            return false;
        }

        return (float)$currentPrice <= 0;
    }

    /**
     * 购买课程（创建待支付记录）
     *
     * @param int $memberId
     * @param int $courseId
     * @param string $phone
     * @param string $ageRange
     * @return array
     * @throws \Exception
     */
    public function preparePurchase(int $memberId, int $courseId, string $phone, string $ageRange): array
    {
        $course = $this->getCourseDetail($courseId);

        if (!$course) {
            throw new \Exception('课程不存在');
        }

        if ($course->is_free == 1) {
            throw new \Exception('该课程是免费课程，请直接领取');
        }

        if (AppMemberCourse::hasCourse($memberId, $courseId)) {
            throw new \Exception('您已购买过该课程');
        }

        // 返回支付信息，供前端调起支付
        return [
            'courseId'      => $courseId,
            'courseTitle'   => $course->course_title,
            'price'         => $course->current_price,
            'originalPrice' => $course->original_price,
            'phone'         => $phone,
            'ageRange'      => $ageRange,
        ];
    }

    /**
     * 获取好课上新列表
     *
     * @param int $limit
     * @return Collection
     */
    public function getNewCourses(int $limit = 10): Collection
    {
        return AppCourseBase::query()
            ->select([
                'course_id',
                'course_title',
                'cover_image',
                'current_price',
                'original_price',
                'teacher_name',
            ])
            ->online()
            ->where('is_new', 1)
            ->orderByDesc('publish_time')
            ->orderByDesc('sort_order')
            ->limit($limit)
            ->get();
    }

    /**
     * 获取名师好课列表
     *
     * @param int $limit
     * @return Collection
     */
    public function getRecommendCourses(int $limit = 10): Collection
    {
        return AppCourseBase::query()
            ->select([
                'course_id',
                'course_title',
                'cover_image',
                'current_price',
                'original_price',
                'teacher_name',
            ])
            ->online()
            ->where('is_recommend', 1)
            ->orderByDesc('sort_order')
            ->orderByDesc('enroll_count')
            ->limit($limit)
            ->get();
    }

    /**
     * 获取课程页「大咖直播」卡片列表。
     *
     * 关键规则：
     * 1. 分别查询 live/upcoming/replay 三种状态，各取 limit 条；
     * 2. 合并顺序固定为 live -> upcoming -> replay；
     * 3. replay 使用 third_party_room_id 作为 id 字段返回，避免与 room_id 语义混淆。
     *
     * @param int $limit 每种状态的最大条数
     * @param int $memberId 会员ID，游客传 0
     * @return Collection<int, array<string, mixed>>
     */
    public function getLiveCourses(int $limit = 2, int $memberId = 0): Collection
    {
        $normalizedLimit = $this->normalizeLiveCourseLimit($limit);

        $livingCards = $this->getLivingLiveCards($normalizedLimit);
        $upcomingCards = $this->getUpcomingLiveCards($normalizedLimit, $memberId);
        $replayCards = $this->getReplayLiveCards($normalizedLimit);

        return collect(array_merge($livingCards, $upcomingCards, $replayCards));
    }

    /**
     * 获取直播中卡片列表。
     *
     * 查询规则：
     * 1. 仅取启用且未删除的直播间；
     * 2. 固定限制 is_show_index=1，确保课程页与直播首页展示口径一致；
     * 3. 仅取 live_status=直播中；
     * 4. watchCount 取 app_live_room_stat.total_viewer_count。
     *
     * @param int $limit
     * @return array<int, array<string, mixed>>
     */
    protected function getLivingLiveCards(int $limit): array
    {
        $rows = DB::table('app_live_room as r')
            ->leftJoin('app_live_room_stat as rs', 'rs.room_id', '=', 'r.room_id')
            ->whereNull('r.deleted_at')
            ->where('r.status', AppLiveRoom::STATUS_ENABLED)
            ->where('r.is_show_index', AppLiveRoom::IS_SHOW_INDEX_YES)
            ->where('r.live_status', AppLiveRoom::LIVE_STATUS_LIVING)
            ->select([
                'r.room_id',
                'r.room_title',
                'r.room_cover',
                DB::raw('COALESCE(r.actual_start_time, r.scheduled_start_time) as start_time'),
                DB::raw('COALESCE(rs.total_viewer_count, 0) as watch_count'),
            ])
            // 直播中场景按开播时间由近到远展示，优先返回“更接近当前时刻”的场次。
            ->orderByRaw('COALESCE(r.actual_start_time, r.scheduled_start_time) DESC NULLS LAST')
            ->orderByDesc('r.room_id')
            ->limit($limit)
            ->get();

        return $rows->map(function ($row) {
            return [
                'id'           => (int)($row->room_id ?? 0),
                'title'        => (string)($row->room_title ?? ''),
                'cover'        => $this->buildCoverUrl((string)($row->room_cover ?? '')),
                'startTime'    => $this->formatDateTime($row->start_time ?? null),
                'status'       => 'live',
                'reserveCount' => 0,
                'isReserved'   => false,
                'watchCount'   => (int)($row->watch_count ?? 0),
                'liveToken'    => '',
                'actionText'   => '进入直播',
            ];
        })->values()->all();
    }

    /**
     * 获取直播预告卡片列表。
     *
     * 查询规则：
     * 1. 仅取启用且未删除的直播间；
     * 2. 固定限制 is_show_index=1，确保课程页与直播首页展示口径一致；
     * 3. 仅取 live_status=未开始；
     * 4. 登录态下关联预约表输出 isReserved，游客固定 false。
     *
     * @param int $limit
     * @param int $memberId
     * @return array<int, array<string, mixed>>
     */
    protected function getUpcomingLiveCards(int $limit, int $memberId): array
    {
        $query = DB::table('app_live_room as r')
            ->leftJoin('app_live_room_stat as rs', 'rs.room_id', '=', 'r.room_id')
            ->whereNull('r.deleted_at')
            ->where('r.status', AppLiveRoom::STATUS_ENABLED)
            ->where('r.is_show_index', AppLiveRoom::IS_SHOW_INDEX_YES)
            ->where('r.live_status', AppLiveRoom::LIVE_STATUS_NOT_STARTED);

        $selectFields = [
            'r.room_id',
            'r.room_title',
            'r.room_cover',
            DB::raw('COALESCE(r.actual_start_time, r.scheduled_start_time) as start_time'),
            DB::raw('COALESCE(rs.reserve_count, 0) as reserve_count'),
        ];

        if ($memberId > 0) {
            $query->leftJoin('app_live_room_reserve as rr', function ($join) use ($memberId) {
                $join->on('rr.room_id', '=', 'r.room_id')
                    ->where('rr.member_id', '=', $memberId)
                    ->where('rr.status', '=', AppLiveRoomReserve::STATUS_RESERVED);
            });

            $selectFields[] = DB::raw('CASE WHEN rr.reserve_id IS NULL THEN 0 ELSE 1 END as is_reserved');
        } else {
            // 游客态不查预约明细，固定 false，避免无意义联表。
            $selectFields[] = DB::raw('0 as is_reserved');
        }

        $rows = $query->select($selectFields)
            ->orderByRaw('COALESCE(r.actual_start_time, r.scheduled_start_time) ASC NULLS LAST')
            ->orderByDesc('r.room_id')
            ->limit($limit)
            ->get();

        return $rows->map(function ($row) {
            $isReserved = (int)($row->is_reserved ?? 0) === 1;

            return [
                'id'           => (int)($row->room_id ?? 0),
                'title'        => (string)($row->room_title ?? ''),
                'cover'        => $this->buildCoverUrl((string)($row->room_cover ?? '')),
                'startTime'    => $this->formatDateTime($row->start_time ?? null),
                'status'       => 'upcoming',
                'reserveCount' => (int)($row->reserve_count ?? 0),
                'isReserved'   => $isReserved,
                'watchCount'   => 0,
                'liveToken'    => '',
                'actionText'   => $isReserved ? '已预约' : '预约',
            ];
        })->values()->all();
    }

    /**
     * 获取回放卡片列表。
     *
     * 过滤规则：
     * 1. 仅取转码成功 + 未屏蔽 + player_token 可用的回放；
     * 2. 仅关联 is_show_index=1 的直播间，避免回放绕过首页展示开关；
     * 3. 同一 third_party_room_id 仅保留最新一条回放；
     * 4. 返回 id 使用 third_party_room_id（字符串）。
     *
     * @param int $limit
     * @return array<int, array<string, mixed>>
     */
    protected function getReplayLiveCards(int $limit): array
    {
        $latestPlaybackByRoom = DB::table('app_live_playback as p')
            ->join('app_live_room as r', function ($join) {
                $join->on('r.third_party_room_id', '=', 'p.third_party_room_id')
                    ->whereNull('r.deleted_at')
                    ->where('r.status', '=', AppLiveRoom::STATUS_ENABLED)
                    ->where('r.is_show_index', '=', AppLiveRoom::IS_SHOW_INDEX_YES);
            })
            ->whereNull('p.deleted_at')
            ->where('p.status', AppLivePlayback::STATUS_TRANSCODE_SUCCESS)
            ->where('p.publish_status', AppLivePlayback::PUBLISH_STATUS_UNSHIELDED)
            ->whereNotNull('p.third_party_room_id')
            ->where('p.third_party_room_id', '!=', '')
            ->whereNotNull('p.player_token')
            ->where('p.player_token', '!=', '')
            ->selectRaw(
                'DISTINCT ON (p.third_party_room_id)
                p.third_party_room_id,
                p.id as playback_pk,
                p.play_times as watch_count,
                p.player_token,
                p.create_time as playback_create_time,
                p.preface_url as playback_cover,
                p.name as playback_name,
                r.room_title,
                r.room_cover,
                r.actual_start_time,
                r.scheduled_start_time,
                COALESCE(r.actual_start_time, r.scheduled_start_time, p.create_time) as start_time'
            )
            // DISTINCT ON 依赖先按去重键排序，再按“最新回放”排序。
            ->orderBy('p.third_party_room_id')
            ->orderByDesc('p.create_time')
            ->orderByDesc('p.id');

        $rows = DB::query()
            ->fromSub($latestPlaybackByRoom, 'rp')
            ->select([
                'rp.third_party_room_id',
                'rp.room_title',
                'rp.room_cover',
                'rp.playback_name',
                'rp.playback_cover',
                'rp.start_time',
                'rp.watch_count',
                'rp.player_token',
                'rp.playback_pk',
            ])
            // 回放列表按开播时间由近到远展示，优先最近可看的场次。
            ->orderByRaw('rp.start_time DESC NULLS LAST')
            ->orderByDesc('rp.playback_pk')
            ->limit($limit)
            ->get();

        return $rows->map(function ($row) {
            return [
                'id'           => (string)($row->third_party_room_id ?? ''),
                'title'        => (string)($row->room_title ?: $row->playback_name ?: ''),
                'cover'        => $this->buildCoverUrl(
                    (string)($row->room_cover ?? ''),
                    (string)($row->playback_cover ?? '')
                ),
                'startTime'    => $this->formatDateTime($row->start_time ?? null),
                'status'       => 'replay',
                'reserveCount' => 0,
                'isReserved'   => false,
                'watchCount'   => (int)($row->watch_count ?? 0),
                'liveToken'    => (string)($row->player_token ?? ''),
                'actionText'   => '看回放',
            ];
        })->values()->all();
    }

    /**
     * 规范课程页直播卡片 limit。
     *
     * @param int $limit
     * @return int
     */
    protected function normalizeLiveCourseLimit(int $limit): int
    {
        if ($limit <= 0) {
            return 2;
        }

        return min($limit, 50);
    }

    /**
     * 构建封面地址，主封面缺失时回退备用封面。
     *
     * @param string $primary
     * @param string $fallback
     * @return string
     */
    protected function buildCoverUrl(string $primary, string $fallback = ''): string
    {
        $cover = trim($primary) !== '' ? $primary : $fallback;

        return $this->normalizeUrl($cover);
    }

    /**
     * 规范化 URL。
     *
     * 规则：
     * - http/https 地址直接透传；
     * - 相对路径统一通过文件服务补齐完整 URL；
     * - 空值返回空串。
     *
     * @param string $value
     * @return string
     */
    protected function normalizeUrl(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if (stripos($value, 'http://') === 0 || stripos($value, 'https://') === 0) {
            return $value;
        }

        return (new AppFileUploadService())->generateFileUrl($value);
    }

    /**
     * 统一时间格式输出。
     *
     * @param mixed $value
     * @return string
     */
    protected function formatDateTime($value): string
    {
        if (!$value) {
            return '';
        }

        try {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * 组装课程底部购买/学习动作字段。
     *
     * @param AppCourseBase $course
     * @param bool $isUnlocked
     * @return array{limitPrice:?string, originalPrice:?string, discountPoints:?string, buttonText:string, buttonActionType:string}
     */
    protected function buildBottomActionData(AppCourseBase $course, bool $isUnlocked): array
    {
        if ($isUnlocked) {
            return [
                'limitPrice'       => null,
                'originalPrice'    => null,
                'discountPoints'   => null,
                'buttonText'       => '去学习',
                'buttonActionType' => 'learn',
            ];
        }

        $isFree = $this->isCourseFreeForDisplay($course);

        return [
            'limitPrice'       => $this->normalizePriceValue($course->current_price),
            'originalPrice'    => $this->normalizePriceValue($course->original_price),
            'discountPoints'   => $isFree ? (string)intval(floatval($course->original_price) * 100) : null,
            'buttonText'       => $isFree ? '免费领取课程' : '立即购买',
            'buttonActionType' => $isFree ? 'free_receive' : 'buy',
        ];
    }

    /**
     * 判断课程是否按免费课程展示购买行为。
     *
     * 规则与旧版详情资源保持一致：
     * 1. is_free=1 直接视为免费；
     * 2. is_free=0 且 original_price=0 时按免费兜底。
     *
     * @param AppCourseBase $course
     * @return bool
     */
    protected function isCourseFreeForDisplay(AppCourseBase $course): bool
    {
        if ((int)$course->is_free === 1) {
            return true;
        }

        $originalPrice = $course->original_price;
        if (is_string($originalPrice)) {
            $originalPrice = trim($originalPrice);
        }

        if ($originalPrice === '' || !is_numeric($originalPrice)) {
            return false;
        }

        return (float)$originalPrice == 0.0;
    }

    /**
     * 金额统一转为去尾零字符串。
     *
     * @param mixed $value
     * @return string
     */
    protected function normalizePriceValue($value): string
    {
        if (is_string($value)) {
            $value = trim($value);
        }

        if (!is_numeric($value)) {
            return '0';
        }

        $formatted = number_format((float)$value, 2, '.', '');
        $formatted = rtrim(rtrim($formatted, '0'), '.');

        if ($formatted === '' || $formatted === '-0') {
            return '0';
        }

        return $formatted;
    }

    /**
     * 组装学习次数展示文案。
     *
     * @param int $enrollCount
     * @return string
     */
    protected function buildStudyCountText(int $enrollCount): string
    {
        if ($enrollCount < 0) {
            $enrollCount = 0;
        }

        return $enrollCount . '次';
    }

    /**
     * 生成课程简介文本（纯文本）。
     *
     * @param AppCourseBase $course
     * @return string
     */
    protected function buildCourseIntroText(AppCourseBase $course): string
    {
        $brief = trim((string)($course->brief ?? ''));
        if ($brief !== '') {
            return $brief;
        }

        $description = trim((string)($course->description ?? ''));
        if ($description === '') {
            return '';
        }

        return trim(strip_tags($description));
    }

    /**
     * 格式化章节日期文案。
     *
     * 优先级：
     * 1. chapter_start_time；
     * 2. unlock_date；
     * 3. 无可用日期返回空串。
     *
     * @param AppCourseChapter $chapter
     * @return string
     */
    protected function formatChapterDateText(AppCourseChapter $chapter): string
    {
        if ($chapter->chapter_start_time) {
            return $chapter->chapter_start_time->format('Y年m月d日');
        }

        if ($chapter->unlock_date) {
            return $chapter->unlock_date->format('Y年m月d日');
        }

        return '';
    }

    /**
     * 将秒级时长转换为分钟展示文案。
     *
     * @param int $duration
     * @return string
     */
    protected function formatChapterDurationText(int $duration): string
    {
        if ($duration <= 0) {
            return '';
        }

        $minutes = (int)ceil($duration / 60);
        if ($minutes <= 0) {
            $minutes = 1;
        }

        return $minutes . '分钟';
    }

}
