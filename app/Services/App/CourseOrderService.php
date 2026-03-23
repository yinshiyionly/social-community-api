<?php

namespace App\Services\App;

use App\Models\App\AppCourseBase;
use App\Models\App\AppCourseOrder;
use App\Models\App\AppCourseOrderPayLog;
use App\Models\App\AppMemberBase;
use App\Models\App\AppMemberCourse;
use App\Models\App\AppMemberSchedule;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * App 端课程订单服务。
 *
 * 职责：
 * 1. 处理课程订单创建、支付状态查询、退款与支付回调；
 * 2. 处理我的订单列表查询与订单状态映射；
 * 3. 处理支付成功后的课程发放、退款后的课程回收等副作用。
 */
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
     * 获取当前登录用户的课程订单列表。
     *
     * 关键规则：
     * 1. 查询前先自动关闭已过期且未支付订单，保证列表状态口径一致；
     * 2. status 仅支持 unpaid/paid/closed/refunded 四种枚举，未传则不过滤；
     * 3. 仅返回当前用户订单，按 order_id 倒序输出，确保最新订单优先展示。
     *
     * @param int $memberId
     * @param int $page
     * @param int $pageSize
     * @param string|null $status
     * @return LengthAwarePaginator
     */
    public function getMemberOrderList(int $memberId, int $page, int $pageSize, ?string $status = null): LengthAwarePaginator
    {
        $this->closeExpiredPendingOrders($memberId);

        $query = AppCourseOrder::query()
            ->select([
                'order_id',
                'order_no',
                'course_id',
                'course_title',
                'paid_amount',
                'pay_status',
                'created_at',
            ])
            ->where('member_id', $memberId);

        $payStatus = $this->mapOrderListStatusToPayStatus($status);
        if ($payStatus !== null) {
            $query->where('pay_status', $payStatus);
        }

        $query->orderByDesc('order_id');

        return $query->paginate($pageSize, ['*'], 'page', $page);
    }

    /**
     * 自动关闭当前用户已过期的待支付订单。
     *
     * 说明：
     * - 仅处理 pay_status=待支付 且 expire_time 小于当前时间的记录；
     * - 使用批量更新避免逐条 close 带来的额外写入成本；
     * - 该步骤不影响已支付/已退款订单，避免误改历史交易状态。
     *
     * @param int $memberId
     * @return void
     */
    protected function closeExpiredPendingOrders(int $memberId): void
    {
        AppCourseOrder::query()
            ->where('member_id', $memberId)
            ->where('pay_status', AppCourseOrder::PAY_STATUS_PENDING)
            ->whereNotNull('expire_time')
            ->where('expire_time', '<', now())
            ->update([
                'pay_status' => AppCourseOrder::PAY_STATUS_CLOSED,
                'updated_at' => now(),
            ]);
    }

    /**
     * 将订单列表状态筛选值映射为数据库支付状态。
     *
     * @param string|null $status
     * @return int|null
     */
    protected function mapOrderListStatusToPayStatus(?string $status): ?int
    {
        if (!is_string($status) || trim($status) === '') {
            return null;
        }

        $map = [
            'unpaid' => AppCourseOrder::PAY_STATUS_PENDING,
            'paid' => AppCourseOrder::PAY_STATUS_PAID,
            'refunded' => AppCourseOrder::PAY_STATUS_REFUNDED,
            'closed' => AppCourseOrder::PAY_STATUS_CLOSED,
        ];

        return $map[strtolower(trim($status))] ?? null;
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
            'provider' => 'wxpay',
            'orderId' => $order->order_no,
            'payStatus' => $this->formatPayStatus((int)$order->pay_status),
            'payStatusCode' => (int)$order->pay_status,
            'expireTime' => optional($order->expire_time)->format('Y-m-d H:i:s'),
            'orderInfo' => $wechatAppPayParams,
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
     * 课程订单退款（微信 v2）
     *
     * @throws \Exception
     */
    public function refundWechatOrder(int $memberId, string $orderNo, string $reason = '', ?string $clientIp = null): array
    {
        try {
            return DB::transaction(function () use ($memberId, $orderNo, $reason, $clientIp) {
                $order = AppCourseOrder::query()
                    ->where('member_id', $memberId)
                    ->where('order_no', $orderNo)
                    ->lockForUpdate()
                    ->first();

                if (!$order) {
                    throw new \Exception('订单不存在');
                }

                if ((int)$order->pay_status === AppCourseOrder::PAY_STATUS_REFUNDED) {
                    return $this->buildRefundResult($order);
                }

                if ((int)$order->pay_status !== AppCourseOrder::PAY_STATUS_PAID) {
                    throw new \Exception('当前订单状态不支持退款');
                }

                if ((int)$order->pay_type !== AppCourseOrder::PAY_TYPE_WECHAT) {
                    throw new \Exception('仅支持微信支付订单退款');
                }

                if ((int)$order->refund_status === AppCourseOrder::REFUND_STATUS_REFUNDED) {
                    return $this->buildRefundResult($order);
                }

                $refundResult = $this->createWechatRefund($order, $reason);
                $refundAmountFen = (int)($refundResult['refund_fee'] ?? 0);
                $orderAmountFen = $this->toFen($order->paid_amount);

                if ($refundAmountFen > 0 && $refundAmountFen !== $orderAmountFen) {
                    throw new \Exception('微信退款金额与订单金额不一致');
                }

                $order->pay_status = AppCourseOrder::PAY_STATUS_REFUNDED;
                $order->refund_status = AppCourseOrder::REFUND_STATUS_REFUNDED;
                $order->refund_amount = $order->paid_amount;
                $order->refund_reason = $reason !== '' ? $reason : (string)($order->refund_reason ?? '');
                $order->refund_time = now();
                $order->save();

                $this->createPayLog(
                    $order,
                    AppCourseOrderPayLog::PAY_RESULT_SUCCESS,
                    (string)($refundResult['refund_id'] ?? $refundResult['out_refund_no'] ?? ''),
                    [
                        'refund' => $refundResult,
                    ],
                    $clientIp,
                    '微信退款成功'
                );

                $this->revokeCourseForRefund($order);

                return $this->buildRefundResult($order);
            });
        } catch (\Exception $e) {
            $order = AppCourseOrder::query()
                ->where('member_id', $memberId)
                ->where('order_no', $orderNo)
                ->first();

            if ($order && (int)$order->pay_status === AppCourseOrder::PAY_STATUS_PAID) {
                $this->createPayLog(
                    $order,
                    AppCourseOrderPayLog::PAY_RESULT_FAIL,
                    null,
                    [
                        'refund_error' => $e->getMessage(),
                        'order_no' => $orderNo,
                    ],
                    $clientIp,
                    '微信退款失败'
                );
            }

            throw $e;
        }
    }

    /**
     * 构建退款响应
     */
    protected function buildRefundResult(AppCourseOrder $order): array
    {
        return [
            'orderNo' => $order->order_no,
            'payStatus' => $this->formatPayStatus((int)$order->pay_status),
            'payStatusCode' => (int)$order->pay_status,
            'refundStatus' => (int)$order->refund_status,
            'refundAmount' => number_format((float)$order->refund_amount, 2, '.', ''),
            'refundTime' => optional($order->refund_time)->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * 处理微信支付回调（v2 XML）
     *
     * @param string $rawXml
     * @param string|null $clientIp
     * @throws \Exception
     */
    public function handleWechatNotify(string $rawXml, ?string $clientIp = null): void
    {
        if (trim($rawXml) === '') {
            Log::warning('微信回调内容为空', [
                'client_ip' => $clientIp,
            ]);
            throw new \Exception('微信回调内容为空');
        }

        try {
            $callbackData = $this->xmlToArray($rawXml);
        } catch (\Exception $e) {
            Log::warning('微信回调XML解析失败', [
                'client_ip' => $clientIp,
                'raw_length' => strlen($rawXml),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        if (empty($callbackData)) {
            Log::warning('微信回调XML解析后为空', [
                'client_ip' => $clientIp,
            ]);
            throw new \Exception('微信回调解析失败');
        }

        if (!$this->verifyWechatV2Sign($callbackData)) {
            Log::warning('微信回调验签失败', [
                'client_ip' => $clientIp,
                'order_no' => (string)($callbackData['out_trade_no'] ?? ''),
                'notify' => $this->maskSensitiveWechatPayload($callbackData),
            ]);
            throw new \Exception('微信回调验签失败');
        }

        $orderNo = (string)($callbackData['out_trade_no'] ?? '');
        $returnCode = strtoupper((string)($callbackData['return_code'] ?? ''));
        $resultCode = strtoupper((string)($callbackData['result_code'] ?? ''));

        if ($orderNo === '') {
            throw new \Exception('微信回调缺少订单号');
        }

        if ($returnCode !== 'SUCCESS' || $resultCode !== 'SUCCESS') {
            $errorMsg = (string)($callbackData['err_code_des'] ?? $callbackData['return_msg'] ?? '支付失败');
            $this->writeFailPayLogByOrderNo($orderNo, $callbackData, $clientIp, '支付状态非SUCCESS:' . $errorMsg);
            throw new \Exception($errorMsg === '' ? '支付失败' : $errorMsg);
        }

        $notifyData = [
            'out_trade_no' => $orderNo,
            'transaction_id' => (string)($callbackData['transaction_id'] ?? ''),
            'amount' => [
                'total' => (int)($callbackData['total_fee'] ?? 0),
            ],
            'raw' => $callbackData,
        ];

        $this->markOrderPaidAndGrantCourse($notifyData, $clientIp);
    }

    /**
     * 微信回调成功 XML
     */
    public function getWechatNotifySuccessXml(): string
    {
        return $this->buildWechatNotifyResponseXml('SUCCESS', 'OK');
    }

    /**
     * 微信回调失败 XML
     */
    public function getWechatNotifyFailXml(string $message = 'FAIL'): string
    {
        return $this->buildWechatNotifyResponseXml('FAIL', $message);
    }

    /**
     * 标记订单已支付并发放课程（幂等）
     *
     * 关键规则：
     * 1. 仅待支付订单允许迁移为已支付；
     * 2. 支付成功并发课完成后，事务提交后触发 `first_purchase` 成长任务积分；
     * 3. 积分触发失败仅记录日志，不影响支付主链路成功。
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
        $firstPurchaseTriggerPayload = null;

        DB::transaction(function () use (
            $orderNo,
            $tradeNo,
            $notifyAmountFen,
            $notifyData,
            $clientIp,
            &$firstPurchaseTriggerPayload
        ) {
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

            // 仅在“待支付 -> 已支付”状态迁移成功时记录触发信息，事务提交后再发积分任务。
            $firstPurchaseTriggerPayload = [
                'member_id' => (int)$order->member_id,
                'order_no' => (string)$order->order_no,
                'client_ip' => $clientIp,
            ];
        });

        if (!is_array($firstPurchaseTriggerPayload)) {
            return;
        }

        $this->triggerFirstPurchasePoint(
            (int)$firstPurchaseTriggerPayload['member_id'],
            (string)$firstPurchaseTriggerPayload['order_no'],
            $firstPurchaseTriggerPayload['client_ip']
        );
    }

    /**
     * 创建微信 APP 调起参数（v2）
     *
     * @param AppCourseOrder $order
     * @return array
     * @throws \Exception
     */
    protected function createWechatAppPayParams(AppCourseOrder $order): array
    {
        $config = $this->buildWechatPayConfig();

        $description = (string)($order->course_title ?: '课程购买');
        if (function_exists('mb_strimwidth')) {
            $description = mb_strimwidth($description, 0, 120, '', 'UTF-8');
        }

        $unifiedOrderPayload = [
            'appid' => $config['app_id'],
            'mch_id' => $config['mch_id'],
            'nonce_str' => $this->generateNonceStr(),
            'body' => $description,
            'out_trade_no' => $order->order_no,
            'total_fee' => $this->toFen($order->paid_amount),
            'spbill_create_ip' => $this->normalizeClientIp($order->client_ip),
            'notify_url' => $config['notify_url'],
            'trade_type' => 'APP',
            'sign_type' => $config['sign_type'],
        ];
        $unifiedOrderPayload['sign'] = $this->buildWechatV2Sign($unifiedOrderPayload);

        $requestXml = $this->arrayToXml($unifiedOrderPayload);
        $url = $config['api_base_v2'] . '/pay/unifiedorder';

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Content-Type' => 'application/xml',
                    'Accept' => 'application/xml',
                ])
                ->send('POST', $url, ['body' => $requestXml]);
        } catch (\Throwable $e) {
            Log::error('微信v2统一下单请求异常', [
                'order_no' => $order->order_no,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception('微信支付请求失败，请稍后重试');
        }

        $responseXml = (string)$response->body();
        if (trim($responseXml) === '') {
            Log::warning('微信v2统一下单响应为空', [
                'order_no' => $order->order_no,
            ]);
            throw new \Exception('微信支付响应为空');
        }

        try {
            $unifiedOrderResult = $this->xmlToArray($responseXml);
        } catch (\Exception $e) {
            Log::warning('微信v2统一下单响应XML解析失败', [
                'order_no' => $order->order_no,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        $returnCode = strtoupper((string)($unifiedOrderResult['return_code'] ?? ''));
        if ($returnCode !== 'SUCCESS') {
            $returnMsg = (string)($unifiedOrderResult['return_msg'] ?? '微信支付下单失败');
            Log::warning('微信v2统一下单通信失败', [
                'order_no' => $order->order_no,
                'return_code' => $returnCode,
                'return_msg' => $returnMsg,
            ]);
            throw new \Exception($returnMsg === '' ? '微信支付下单失败' : $returnMsg);
        }

        if (!$this->verifyWechatV2Sign($unifiedOrderResult)) {
            Log::warning('微信v2统一下单响应验签失败', [
                'order_no' => $order->order_no,
                'response' => $this->maskSensitiveWechatPayload($unifiedOrderResult),
            ]);
            throw new \Exception('微信支付响应验签失败');
        }

        $resultCode = strtoupper((string)($unifiedOrderResult['result_code'] ?? ''));
        if ($resultCode !== 'SUCCESS') {
            $errorMsg = (string)($unifiedOrderResult['err_code_des'] ?? $unifiedOrderResult['return_msg'] ?? '微信支付下单失败');
            Log::warning('微信v2统一下单业务失败', [
                'order_no' => $order->order_no,
                'result_code' => $resultCode,
                'err_code' => (string)($unifiedOrderResult['err_code'] ?? ''),
                'err_code_des' => $errorMsg,
            ]);
            throw new \Exception($errorMsg === '' ? '微信支付下单失败' : $errorMsg);
        }

        $prepayId = (string)($unifiedOrderResult['prepay_id'] ?? '');
        if ($prepayId === '') {
            Log::warning('微信v2统一下单缺少prepay_id', [
                'order_no' => $order->order_no,
                'response' => $this->maskSensitiveWechatPayload($unifiedOrderResult),
            ]);
            throw new \Exception('微信支付响应缺少prepay_id');
        }

        $appPayParams = [
            'appid' => $config['app_id'],
            'partnerid' => $config['mch_id'],
            'prepayid' => $prepayId,
            'package' => 'Sign=WXPay',
            'noncestr' => $this->generateNonceStr(),
            'timestamp' => (string)time(),
        ];

        $appPayParams['sign'] = $this->buildWechatV2Sign([
            'appid' => $appPayParams['appid'],
            'partnerid' => $appPayParams['partnerid'],
            'prepayid' => $appPayParams['prepayid'],
            'package' => $appPayParams['package'],
            'noncestr' => $appPayParams['noncestr'],
            'timestamp' => $appPayParams['timestamp'],
        ]);

        return $appPayParams;
    }

    /**
     * 调用微信 v2 退款接口
     *
     * @throws \Exception
     */
    protected function createWechatRefund(AppCourseOrder $order, string $reason = ''): array
    {
        $config = $this->buildWechatPayConfig();

        $refundDesc = $reason !== '' ? $reason : '课程订单退款';
        if (function_exists('mb_strimwidth')) {
            $refundDesc = mb_strimwidth($refundDesc, 0, 80, '', 'UTF-8');
        } elseif (strlen($refundDesc) > 80) {
            $refundDesc = substr($refundDesc, 0, 80);
        }

        $payload = [
            'appid' => $config['app_id'],
            'mch_id' => $config['mch_id'],
            'nonce_str' => $this->generateNonceStr(),
            'out_trade_no' => $order->order_no,
            'out_refund_no' => $this->buildWechatRefundNo($order->order_no),
            'total_fee' => $this->toFen($order->paid_amount),
            'refund_fee' => $this->toFen($order->paid_amount),
            'refund_desc' => $refundDesc,
            'sign_type' => $config['sign_type'],
        ];
        $payload['sign'] = $this->buildWechatV2Sign($payload);

        $requestXml = $this->arrayToXml($payload);
        $url = $config['api_base_v2'] . '/secapi/pay/refund';

        $certPath = $this->resolveWechatPemPath((string)$config['mch_public_cert_path_v2'], 'wechat_pay_cert');
        $keyPath = $this->resolveWechatPemPath((string)$config['mch_secret_cert_v2'], 'wechat_pay_key');

        try {
            $response = Http::timeout(15)
                ->withOptions([
                    'cert' => $certPath,
                    'ssl_key' => $keyPath,
                ])
                ->withHeaders([
                    'Content-Type' => 'application/xml',
                    'Accept' => 'application/xml',
                ])
                ->send('POST', $url, ['body' => $requestXml]);
        } catch (\Throwable $e) {
            Log::error('微信v2退款请求异常', [
                'order_no' => $order->order_no,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception('微信退款请求失败，请稍后重试');
        }

        $responseXml = (string)$response->body();
        if (trim($responseXml) === '') {
            Log::warning('微信v2退款响应为空', [
                'order_no' => $order->order_no,
            ]);
            throw new \Exception('微信退款响应为空');
        }

        try {
            $result = $this->xmlToArray($responseXml);
        } catch (\Exception $e) {
            Log::warning('微信v2退款响应XML解析失败', [
                'order_no' => $order->order_no,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        $returnCode = strtoupper((string)($result['return_code'] ?? ''));
        if ($returnCode !== 'SUCCESS') {
            $returnMsg = (string)($result['return_msg'] ?? '微信退款失败');
            Log::warning('微信v2退款通信失败', [
                'order_no' => $order->order_no,
                'return_code' => $returnCode,
                'return_msg' => $returnMsg,
            ]);
            throw new \Exception($returnMsg === '' ? '微信退款失败' : $returnMsg);
        }

        if (!$this->verifyWechatV2Sign($result)) {
            Log::warning('微信v2退款响应验签失败', [
                'order_no' => $order->order_no,
                'response' => $this->maskSensitiveWechatPayload($result),
            ]);
            throw new \Exception('微信退款响应验签失败');
        }

        $resultCode = strtoupper((string)($result['result_code'] ?? ''));
        if ($resultCode !== 'SUCCESS') {
            $errorMsg = (string)($result['err_code_des'] ?? $result['return_msg'] ?? '微信退款失败');
            Log::warning('微信v2退款业务失败', [
                'order_no' => $order->order_no,
                'result_code' => $resultCode,
                'err_code' => (string)($result['err_code'] ?? ''),
                'err_code_des' => $errorMsg,
            ]);
            throw new \Exception($errorMsg === '' ? '微信退款失败' : $errorMsg);
        }

        return $result;
    }

    /**
     * 退款后回收课程权益
     */
    protected function revokeCourseForRefund(AppCourseOrder $order): void
    {
        try {
            $memberCourse = AppMemberCourse::withTrashed()
                ->where('member_id', $order->member_id)
                ->where('course_id', $order->course_id)
                ->where('order_no', $order->order_no)
                ->lockForUpdate()
                ->first();

            if (!$memberCourse || $memberCourse->trashed()) {
                return;
            }

            $memberCourseId = (int)$memberCourse->id;
            $memberCourse->is_expired = 1;
            $memberCourse->expire_time = now();
            $memberCourse->save();
            $memberCourse->delete();

            AppMemberSchedule::query()
                ->where('member_course_id', $memberCourseId)
                ->delete();

            AppCourseBase::query()
                ->where('course_id', $order->course_id)
                ->where('enroll_count', '>', 0)
                ->decrement('enroll_count');
        } catch (\Throwable $e) {
            Log::error('退款后回收课程权益失败', [
                'order_no' => $order->order_no,
                'member_id' => $order->member_id,
                'course_id' => $order->course_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 发放课程（每次支付成功都重建章节课表）。
     *
     * 关键规则：
     * 1. member_course 首次创建时才累加报名人数；
     * 2. 无论首次购买还是重购，均按当前章节规则重建课表；
     * 3. 课表重建在同一事务内执行，避免支付成功后权益数据不一致。
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
        }

        // 支付发课后统一重建章节课表，确保重购场景也能拿到最新章节排课。
        $this->learningCenterService->generateSchedule(
            (int)$order->member_id,
            (int)$order->course_id,
            (int)$memberCourse->id,
            $enrollTime
        );
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
            'notify' => $this->maskSensitiveWechatPayload($response),
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
                'response' => $this->maskSensitiveWechatPayload($response),
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
     * 过滤微信回调中的敏感字段，避免日志泄露签名或密钥
     */
    protected function maskSensitiveWechatPayload(array $payload): array
    {
        $masked = [];
        foreach ($payload as $key => $value) {
            $normalizedKey = strtolower((string)$key);
            if (in_array($normalizedKey, ['sign', 'key', 'api_key', 'mch_secret_key', 'mch_secret_key_v2'], true)) {
                continue;
            }

            if (is_array($value)) {
                $masked[$key] = $this->maskSensitiveWechatPayload($value);
                continue;
            }

            $masked[$key] = $value;
        }

        return $masked;
    }

    /**
     * 构建微信支付配置（v2 主链路）
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
        $mchSecretKeyV2 = (string)($wechatPayConfig['mch_secret_key_v2'] ?? '');
        $mchSecretCertV2 = (string)($wechatPayConfig['mch_secret_cert_v2'] ?? $wechatPayConfig['mch_secret_cert'] ?? '');
        $mchPublicCertPathV2 = (string)($wechatPayConfig['mch_public_cert_path_v2'] ?? $wechatPayConfig['mch_public_cert_path'] ?? '');
        $signType = strtoupper((string)($wechatPayConfig['sign_type'] ?? 'MD5'));
        $apiBaseV2 = rtrim((string)($wechatPayConfig['api_base_v2'] ?? 'https://api.mch.weixin.qq.com'), '/');

        if ($signType !== 'MD5' && $signType !== 'HMAC-SHA256') {
            $signType = 'MD5';
        }

        if ($appId === '' || $mchId === '' || $mchSecretKeyV2 === '') {
            throw new \Exception('微信支付v2配置不完整，请联系管理员');
        }

        return [
            'app_id' => $appId,
            'mch_id' => $mchId,
            'mch_secret_key_v2' => $mchSecretKeyV2,
            'mch_secret_cert_v2' => $mchSecretCertV2,
            'mch_public_cert_path_v2' => $mchPublicCertPathV2,
            'notify_url' => $notifyUrl,
            'sign_type' => $signType,
            'api_base_v2' => $apiBaseV2,
        ];
    }

    /**
     * 微信 v2 签名
     *
     * @param array $params
     * @return string
     * @throws \Exception
     */
    protected function buildWechatV2Sign(array $params): string
    {
        $config = $this->buildWechatPayConfig();
        $apiKey = $config['mch_secret_key_v2'];
        $signType = $config['sign_type'];

        unset($params['sign']);

        ksort($params);
        $pairs = [];
        foreach ($params as $key => $value) {
            if ($value === null || $value === '' || is_array($value)) {
                continue;
            }
            $pairs[] = $key . '=' . $value;
        }

        $query = implode('&', $pairs) . '&key=' . $apiKey;

        if ($signType === 'HMAC-SHA256') {
            return strtoupper(hash_hmac('sha256', $query, $apiKey));
        }

        return strtoupper(md5($query));
    }

    /**
     * 微信 v2 验签
     *
     * @param array $params
     * @return bool
     * @throws \Exception
     */
    protected function verifyWechatV2Sign(array $params): bool
    {
        $sign = strtoupper((string)($params['sign'] ?? ''));
        if ($sign === '') {
            return false;
        }

        return hash_equals($sign, $this->buildWechatV2Sign($params));
    }

    /**
     * 数组转 XML
     *
     * @param array $params
     * @return string
     */
    protected function arrayToXml(array $params): string
    {
        $xml = '<xml>';
        foreach ($params as $key => $value) {
            if ($value === null) {
                continue;
            }

            $key = trim((string)$key);
            $value = (string)$value;

            if ($value !== '' && ctype_digit($value)) {
                $xml .= '<' . $key . '>' . $value . '</' . $key . '>';
            } else {
                $safeValue = str_replace(']]>', ']]]]><![CDATA[>', $value);
                $xml .= '<' . $key . '><![CDATA[' . $safeValue . ']]></' . $key . '>';
            }
        }
        $xml .= '</xml>';

        return $xml;
    }

    /**
     * XML 转数组
     *
     * @param string $xml
     * @return array
     * @throws \Exception
     */
    protected function xmlToArray(string $xml): array
    {
        if (trim($xml) === '') {
            throw new \Exception('XML为空');
        }

        $previous = libxml_use_internal_errors(true);

        $element = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($element === false) {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
            throw new \Exception('XML解析失败');
        }

        $json = json_encode($element);
        $array = json_decode((string)$json, true);

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return is_array($array) ? $array : [];
    }

    /**
     * 构建微信回调响应 XML
     */
    protected function buildWechatNotifyResponseXml(string $code, string $msg): string
    {
        return $this->arrayToXml([
            'return_code' => strtoupper($code),
            'return_msg' => $msg,
        ]);
    }

    /**
     * 生成随机字符串
     */
    protected function generateNonceStr(int $length = 32): string
    {
        $length = $length > 0 ? $length : 32;

        try {
            $bytes = random_bytes((int)ceil($length / 2));
            return substr(bin2hex($bytes), 0, $length);
        } catch (\Throwable $e) {
            return substr(md5(uniqid((string)mt_rand(), true)), 0, $length);
        }
    }

    /**
     * 生成微信退款单号（同订单幂等）
     */
    protected function buildWechatRefundNo(string $orderNo): string
    {
        $refundNo = 'RF' . trim($orderNo);

        return strlen($refundNo) > 64 ? substr($refundNo, 0, 64) : $refundNo;
    }

    /**
     * 解析证书路径：支持直接传文件路径或 PEM 文本内容
     *
     * @throws \Exception
     */
    protected function resolveWechatPemPath(string $value, string $prefix): string
    {
        $value = trim($value);
        if ($value === '') {
            throw new \Exception('微信退款证书配置不完整');
        }

        if (is_file($value)) {
            return $value;
        }

        if (strpos($value, '-----BEGIN') === false) {
            throw new \Exception('微信退款证书格式无效，请检查配置');
        }

        $dir = storage_path('app/wechat-pay-cert');
        if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
            throw new \Exception('微信退款证书目录创建失败');
        }

        $path = $dir . DIRECTORY_SEPARATOR . $prefix . '_' . md5($value) . '.pem';
        if (!is_file($path)) {
            if (@file_put_contents($path, $value) === false) {
                throw new \Exception('微信退款证书写入失败');
            }
            @chmod($path, 0600);
        }

        return $path;
    }

    /**
     * 规范化支付 IP
     */
    protected function normalizeClientIp(?string $ip): string
    {
        if (!is_string($ip) || trim($ip) === '') {
            return '127.0.0.1';
        }

        $ip = trim($ip);
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $ip;
        }

        return '127.0.0.1';
    }

    /**
     * 触发首次购买成长任务积分。
     *
     * 关键约束：
     * 1. 仅在订单完成支付并发课成功后触发；
     * 2. biz_id 使用 order_no，便于排查与幂等追踪；
     * 3. 触发异常仅记录日志，不影响支付成功主链路。
     *
     * @param int $memberId
     * @param string $orderNo
     * @param string|null $clientIp
     * @return void
     */
    protected function triggerFirstPurchasePoint(int $memberId, string $orderNo, ?string $clientIp = null): void
    {
        try {
            $pointService = new PointService();
            $pointService->triggerTaskEarn($memberId, 'first_purchase', $orderNo, $clientIp);
        } catch (\Throwable $e) {
            Log::error('触发首次购买积分任务失败', [
                'member_id' => $memberId,
                'task_code' => 'first_purchase',
                'biz_id' => $orderNo,
                'error' => $e->getMessage(),
            ]);
        }
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
