<?php

namespace App\Services\App;

use App\Models\App\AppCourseBase;
use App\Models\App\AppCourseOrder;
use App\Models\App\AppCourseOrderPayLog;
use App\Models\App\AppMemberBase;
use App\Models\App\AppMemberCourse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yansongda\Pay\Pay;
use Yansongda\Supports\Collection as YansongdaCollection;

class CourseOrderService
{
    const ORDER_EXPIRE_MINUTES = 30;

    /**
     * @var LearningCenterService
     */
    protected $learningCenterService;

    public function __construct(LearningCenterService $learningCenterService)
    {
        $this->learningCenterService = $learningCenterService;
    }

    /**
     * 创建或复用微信 APP 支付订单
     *
     * @param int $memberId
     * @param int $courseId
     * @param string $phone
     * @param string $ageRange
     * @param string|null $clientIp
     * @param string|null $userAgent
     * @return array
     * @throws \Exception
     */
    public function createWechatAppOrder(
        int $memberId,
        int $courseId,
        string $phone,
        string $ageRange,
        ?string $clientIp = null,
        ?string $userAgent = null
    ): array {
        $order = DB::transaction(function () use ($memberId, $courseId, $phone, $ageRange, $clientIp, $userAgent) {
            $member = AppMemberBase::query()
                ->select(['member_id'])
                ->where('member_id', $memberId)
                ->lockForUpdate()
                ->first();

            if (!$member) {
                throw new \Exception('用户不存在');
            }

            $course = AppCourseBase::query()
                ->select([
                    'course_id',
                    'course_title',
                    'cover_image',
                    'current_price',
                    'original_price',
                    'is_free',
                ])
                ->online()
                ->where('course_id', $courseId)
                ->first();

            if (!$course) {
                throw new \Exception('课程不存在');
            }

            if ((int)$course->is_free === 1 || $this->toFen($course->current_price) <= 0) {
                throw new \Exception('该课程是免费课程，请直接领取');
            }

            $memberCourse = AppMemberCourse::withTrashed()
                ->where('member_id', $memberId)
                ->where('course_id', $courseId)
                ->lockForUpdate()
                ->first();

            if ($memberCourse && !$memberCourse->trashed() && (int)$memberCourse->is_expired === 0) {
                throw new \Exception('您已购买过该课程');
            }

            $pendingOrder = AppCourseOrder::query()
                ->where('member_id', $memberId)
                ->where('course_id', $courseId)
                ->where('pay_status', AppCourseOrder::PAY_STATUS_PENDING)
                ->orderByDesc('order_id')
                ->lockForUpdate()
                ->first();

            if ($pendingOrder) {
                if (!$pendingOrder->isExpired()) {
                    $pendingOrder->enroll_phone = $phone;
                    $pendingOrder->enroll_age_range = $ageRange;
                    $pendingOrder->client_ip = $clientIp;
                    $pendingOrder->user_agent = $userAgent;
                    $pendingOrder->save();

                    return $pendingOrder;
                }

                $pendingOrder->close();
            }

            return AppCourseOrder::query()->create([
                'order_no' => AppCourseOrder::generateOrderNo(),
                'member_id' => $memberId,
                'course_id' => $courseId,
                'course_title' => $course->course_title,
                'course_cover' => $course->cover_image,
                'enroll_phone' => $phone,
                'enroll_age_range' => $ageRange,
                'original_price' => $course->original_price,
                'current_price' => $course->current_price,
                'discount_amount' => 0,
                'coupon_amount' => 0,
                'point_deduct' => 0,
                'point_amount' => 0,
                'paid_amount' => $course->current_price,
                'pay_status' => AppCourseOrder::PAY_STATUS_PENDING,
                'pay_type' => AppCourseOrder::PAY_TYPE_WECHAT,
                'expire_time' => now()->addMinutes(self::ORDER_EXPIRE_MINUTES),
                'client_ip' => $clientIp,
                'user_agent' => $userAgent,
            ]);
        });

        $wechatAppPayParams = $this->createWechatAppPayParams($order);

        return [
            'orderNo' => $order->order_no,
            'payStatus' => $this->formatPayStatus((int)$order->pay_status),
            'payStatusCode' => (int)$order->pay_status,
            'expireTime' => optional($order->expire_time)->format('Y-m-d H:i:s'),
            'wechatAppPayParams' => $wechatAppPayParams,
        ];
    }

    /**
     * 查询订单状态
     *
     * @param int $memberId
     * @param string $orderNo
     * @return array
     * @throws \Exception
     */
    public function getOrderStatus(int $memberId, string $orderNo): array
    {
        $order = AppCourseOrder::query()
            ->where('member_id', $memberId)
            ->where('order_no', $orderNo)
            ->first();

        if (!$order) {
            throw new \Exception('订单不存在');
        }

        if ($order->isPending() && $order->isExpired()) {
            $order->close();
            $order->refresh();
        }

        return [
            'orderNo' => $order->order_no,
            'payStatus' => $this->formatPayStatus((int)$order->pay_status),
            'payStatusCode' => (int)$order->pay_status,
            'payStatusText' => $order->pay_status_text,
            'payType' => $order->pay_type,
            'paidAmount' => number_format((float)$order->paid_amount, 2, '.', ''),
            'expireTime' => optional($order->expire_time)->format('Y-m-d H:i:s'),
            'payTime' => optional($order->pay_time)->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * 处理微信支付回调
     *
     * @param string|null $clientIp
     * @throws \Exception
     */
    public function handleWechatNotify(?string $clientIp = null): void
    {
        $pay = $this->getWechatPayProvider();
        $callbackData = $pay->callback();
        $notifyData = $this->extractWechatNotifyData($callbackData);

        $orderNo = (string)($notifyData['out_trade_no'] ?? '');
        $tradeState = (string)($notifyData['trade_state'] ?? '');

        if ($orderNo === '') {
            throw new \Exception('微信回调缺少订单号');
        }

        if ($tradeState !== 'SUCCESS') {
            $this->writeFailPayLogByOrderNo($orderNo, $notifyData, $clientIp, '支付状态非SUCCESS');
            throw new \Exception('支付状态非SUCCESS');
        }

        $this->markOrderPaidAndGrantCourse($notifyData, $clientIp);
    }

    /**
     * 标记订单已支付并发放课程（幂等）
     *
     * @param array $notifyData
     * @param string|null $clientIp
     * @throws \Exception
     */
    public function markOrderPaidAndGrantCourse(array $notifyData, ?string $clientIp = null): void
    {
        $orderNo = (string)($notifyData['out_trade_no'] ?? '');
        $tradeNo = (string)($notifyData['transaction_id'] ?? '');
        $notifyAmountFen = (int)($notifyData['amount']['total'] ?? 0);

        DB::transaction(function () use ($orderNo, $tradeNo, $notifyAmountFen, $notifyData, $clientIp) {
            $order = AppCourseOrder::query()
                ->where('order_no', $orderNo)
                ->lockForUpdate()
                ->first();

            if (!$order) {
                throw new \Exception('订单不存在');
            }

            if ((int)$order->pay_status === AppCourseOrder::PAY_STATUS_PAID) {
                return;
            }

            if ((int)$order->pay_status !== AppCourseOrder::PAY_STATUS_PENDING) {
                $this->createPayLog(
                    $order,
                    AppCourseOrderPayLog::PAY_RESULT_FAIL,
                    $tradeNo,
                    $notifyData,
                    $clientIp,
                    '订单状态不允许支付'
                );
                throw new \Exception('订单状态不允许支付');
            }

            $orderAmountFen = $this->toFen($order->paid_amount);
            if ($orderAmountFen !== $notifyAmountFen) {
                $this->createPayLog(
                    $order,
                    AppCourseOrderPayLog::PAY_RESULT_FAIL,
                    $tradeNo,
                    $notifyData,
                    $clientIp,
                    '支付金额不一致'
                );
                throw new \Exception('支付金额不一致');
            }

            $order->pay_status = AppCourseOrder::PAY_STATUS_PAID;
            $order->pay_type = AppCourseOrder::PAY_TYPE_WECHAT;
            $order->pay_trade_no = $tradeNo;
            $order->pay_time = now();
            $order->save();

            $this->createPayLog(
                $order,
                AppCourseOrderPayLog::PAY_RESULT_SUCCESS,
                $tradeNo,
                $notifyData,
                $clientIp
            );

            $this->grantCourseForPaidOrder($order);
        });
    }

    /**
     * 创建微信 APP 调起参数
     *
     * @param AppCourseOrder $order
     * @return array
     */
    protected function createWechatAppPayParams(AppCourseOrder $order): array
    {
        $pay = $this->getWechatPayProvider();

        $description = (string)($order->course_title ?: '课程购买');
        if (function_exists('mb_strimwidth')) {
            $description = mb_strimwidth($description, 0, 120, '', 'UTF-8');
        }

        $payload = [
            'out_trade_no' => $order->order_no,
            'description' => $description,
            'amount' => [
                'total' => $this->toFen($order->paid_amount),
            ],
        ];

        $result = $pay->app($payload);

        return $result->all();
    }

    /**
     * 发放课程（首次创建时生成课表并累加报名数）
     *
     * @param AppCourseOrder $order
     * @throws \Exception
     */
    protected function grantCourseForPaidOrder(AppCourseOrder $order): void
    {
        $course = AppCourseBase::query()
            ->select(['course_id', 'valid_days', 'total_chapter'])
            ->where('course_id', $order->course_id)
            ->first();

        if (!$course) {
            throw new \Exception('课程不存在');
        }

        $enrollTime = now();
        $expireTime = null;
        if ((int)$course->valid_days > 0) {
            $expireTime = $enrollTime->copy()->addDays((int)$course->valid_days);
        }

        $memberCourse = AppMemberCourse::withTrashed()
            ->where('member_id', $order->member_id)
            ->where('course_id', $order->course_id)
            ->lockForUpdate()
            ->first();

        $isFirstCreate = false;

        if (!$memberCourse) {
            $memberCourse = AppMemberCourse::query()->create([
                'member_id' => $order->member_id,
                'course_id' => $order->course_id,
                'order_no' => $order->order_no,
                'source_type' => AppMemberCourse::SOURCE_TYPE_PURCHASE,
                'enroll_phone' => $order->enroll_phone,
                'enroll_age_range' => $order->enroll_age_range,
                'paid_amount' => $order->paid_amount,
                'enroll_time' => $enrollTime,
                'expire_time' => $expireTime,
                'is_expired' => 0,
                'total_chapters' => (int)$course->total_chapter,
            ]);
            $isFirstCreate = true;
        } else {
            if ($memberCourse->trashed()) {
                $memberCourse->restore();
            }

            $memberCourse->order_no = $order->order_no;
            $memberCourse->source_type = AppMemberCourse::SOURCE_TYPE_PURCHASE;
            $memberCourse->enroll_phone = $order->enroll_phone;
            $memberCourse->enroll_age_range = $order->enroll_age_range;
            $memberCourse->paid_amount = $order->paid_amount;
            $memberCourse->enroll_time = $enrollTime;
            $memberCourse->expire_time = $expireTime;
            $memberCourse->is_expired = 0;
            $memberCourse->save();
        }

        if ($isFirstCreate) {
            AppCourseBase::query()
                ->where('course_id', $order->course_id)
                ->increment('enroll_count');

            $this->learningCenterService->generateSchedule(
                (int)$order->member_id,
                (int)$order->course_id,
                (int)$memberCourse->id,
                $enrollTime
            );
        }
    }

    /**
     * 写支付日志
     *
     * @param AppCourseOrder $order
     * @param int $payResult
     * @param string|null $tradeNo
     * @param array $response
     * @param string|null $clientIp
     * @param string $remark
     */
    protected function createPayLog(
        AppCourseOrder $order,
        int $payResult,
        ?string $tradeNo,
        array $response,
        ?string $clientIp,
        string $remark = ''
    ): void {
        $payload = [
            'notify' => $response,
        ];

        if ($remark !== '') {
            $payload['remark'] = $remark;
        }

        AppCourseOrderPayLog::query()->create([
            'order_no' => $order->order_no,
            'member_id' => $order->member_id,
            'pay_type' => AppCourseOrder::PAY_TYPE_WECHAT,
            'pay_amount' => $order->paid_amount,
            'trade_no' => $tradeNo,
            'pay_result' => $payResult,
            'pay_response' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'client_ip' => $clientIp,
            'created_at' => now(),
        ]);
    }

    /**
     * 根据订单号写失败日志（用于无法拿到订单实体前）
     *
     * @param string $orderNo
     * @param array $response
     * @param string|null $clientIp
     * @param string $remark
     */
    protected function writeFailPayLogByOrderNo(string $orderNo, array $response, ?string $clientIp, string $remark): void
    {
        $order = AppCourseOrder::query()->where('order_no', $orderNo)->first();

        if (!$order) {
            Log::warning('微信回调失败且订单不存在', [
                'order_no' => $orderNo,
                'response' => $response,
                'remark' => $remark,
            ]);
            return;
        }

        $tradeNo = (string)($response['transaction_id'] ?? '');
        $this->createPayLog(
            $order,
            AppCourseOrderPayLog::PAY_RESULT_FAIL,
            $tradeNo,
            $response,
            $clientIp,
            $remark
        );
    }

    /**
     * 解析微信回调明文业务数据
     *
     * @param YansongdaCollection $callbackData
     * @return array
     */
    protected function extractWechatNotifyData(YansongdaCollection $callbackData): array
    {
        $data = $callbackData->all();

        if (!empty($data['resource']['ciphertext']) && is_array($data['resource']['ciphertext'])) {
            return $data['resource']['ciphertext'];
        }

        if (!empty($data['resource']['ciphertext']) && is_string($data['resource']['ciphertext'])) {
            $decoded = json_decode($data['resource']['ciphertext'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        if (!empty($data['resource']) && is_array($data['resource']) && isset($data['resource']['out_trade_no'])) {
            return $data['resource'];
        }

        return $data;
    }

    /**
     * 获取微信支付 Provider
     *
     * @return \Yansongda\Pay\Provider\Wechat
     */
    protected function getWechatPayProvider()
    {
        $config = $this->buildWechatPayConfig();
        Pay::config($config);

        return Pay::wechat();
    }

    /**
     * 构建 yansongda/pay 配置
     *
     * @return array
     * @throws \Exception
     */
    protected function buildWechatPayConfig(): array
    {
        $wechatPayConfig = config('services.wechat_pay', []);

        $notifyUrl = (string)($wechatPayConfig['notify_url'] ?? '');
        if ($notifyUrl === '') {
            $notifyUrl = rtrim((string)config('app.url'), '/') . '/api/app/v1/course/pay/wechat/notify';
        }

        $appId = (string)($wechatPayConfig['app_id'] ?? '');
        $mchId = (string)($wechatPayConfig['mch_id'] ?? '');
        $mchSecretKey = (string)($wechatPayConfig['mch_secret_key'] ?? '');
        $mchSecretCert = (string)($wechatPayConfig['mch_secret_cert'] ?? '');
        $mchPublicCertPath = (string)($wechatPayConfig['mch_public_cert_path'] ?? '');

        if ($appId === '' || $mchId === '' || $mchSecretKey === '' || $mchSecretCert === '' || $mchPublicCertPath === '') {
            throw new \Exception('微信支付配置不完整，请联系管理员');
        }

        return [
            '_force' => true,
            'wechat' => [
                'default' => [
                    'app_id' => $appId,
                    'mch_id' => $mchId,
                    'mch_secret_key' => $mchSecretKey,
                    'mch_secret_cert' => $mchSecretCert,
                    'mch_public_cert_path' => $mchPublicCertPath,
                    'notify_url' => $notifyUrl,
                    'wechat_public_cert_path' => [],
                    'mode' => Pay::MODE_NORMAL,
                ],
            ],
            'logger' => [
                'enable' => false,
            ],
            'http' => [
                'timeout' => 10.0,
                'connect_timeout' => 10.0,
            ],
        ];
    }

    /**
     * 转换为分
     *
     * @param mixed $amount
     * @return int
     */
    protected function toFen($amount): int
    {
        return (int)round((float)$amount * 100);
    }

    /**
     * 订单支付状态格式化
     *
     * @param int $payStatus
     * @return string
     */
    protected function formatPayStatus(int $payStatus): string
    {
        $map = [
            AppCourseOrder::PAY_STATUS_PENDING => 'pending',
            AppCourseOrder::PAY_STATUS_PAID => 'paid',
            AppCourseOrder::PAY_STATUS_REFUNDED => 'refunded',
            AppCourseOrder::PAY_STATUS_CLOSED => 'closed',
        ];

        return $map[$payStatus] ?? 'unknown';
    }
}
