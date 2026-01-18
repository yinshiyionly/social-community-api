<?php

namespace App\Exceptions;

use App\Constant\AppResponseCode;
use App\Constant\ResponseCode;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        // Log exceptions with context
        $this->reportable(function (Throwable $e) {
            $this->logExceptionWithContext($e);
        });

        // Admin/System 端自定义业务异常
        $this->renderable(function (ApiException $e, $request) {
            return $e->render();
        });

        // App 端自定义业务异常
        $this->renderable(function (AppApiException $e, $request) {
            return $e->render();
        });
    }

    /**
     * 渲染异常为 HTTP 响应
     */
    public function render($request, Throwable $e)
    {
        // 如果是 API 请求，统一返回 JSON 格式
        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->handleApiException($request, $e);
        }

        return parent::render($request, $e);
    }

    /**
     * 判断是否为 App 端请求
     */
    protected function isAppRequest($request): bool
    {
        return $request->is('api/app/*') || $request->is('api/app');
    }

    /**
     * 处理 API 异常
     */
    protected function handleApiException($request, Throwable $e)
    {
        // 根据路由前缀分发到不同的处理器
        if ($this->isAppRequest($request)) {
            return $this->handleAppApiException($request, $e);
        }

        return $this->handleAdminApiException($request, $e);
    }

    /**
     * 处理 Admin/System 端 API 异常
     * 可以暴露详细错误信息便于调试
     */
    protected function handleAdminApiException($request, Throwable $e)
    {
        // 自定义业务异常
        if ($e instanceof ApiException) {
            return response()->json([
                'code' => $e->getCode(),
                'msg' => $e->getMessage(),
                'data' => $e->getData(),
            ]);
        }

        // 验证异常
        if ($e instanceof ValidationException) {
            return response()->json([
                'code' => ResponseCode::INVALID_PARAMS,
                'msg' => $e->validator->errors()->first(),
                'data' => []
            ]);
        }

        // 认证异常
        if ($e instanceof AuthenticationException) {
            return response()->json([
                'code' => ResponseCode::UNAUTHORIZED,
                'msg' => ResponseCode::getMessage(ResponseCode::UNAUTHORIZED),
                'data' => []
            ]);
        }

        // 授权异常
        if ($e instanceof AuthorizationException) {
            return response()->json([
                'code' => ResponseCode::FORBIDDEN,
                'msg' => $e->getMessage() ?: ResponseCode::getMessage(ResponseCode::FORBIDDEN),
                'data' => []
            ]);
        }

        // 模型未找到异常
        if ($e instanceof ModelNotFoundException) {
            return response()->json([
                'code' => ResponseCode::DATA_NOT_FOUND,
                'msg' => ResponseCode::getMessage(ResponseCode::DATA_NOT_FOUND),
                'data' => [],
            ]);
        }

        // 404 异常
        if ($e instanceof NotFoundHttpException) {
            return response()->json([
                'code' => ResponseCode::NOT_FOUND,
                'msg' => ResponseCode::getMessage(ResponseCode::NOT_FOUND),
                'data' => []
            ]);
        }

        // 方法不允许异常
        if ($e instanceof MethodNotAllowedHttpException) {
            Log::error('方法不允许异常', ['content' => $e->getMessage()]);
            return response()->json([
                'code' => ResponseCode::METHOD_NOT_ALLOWED,
                'msg' => ResponseCode::getMessage(ResponseCode::METHOD_NOT_ALLOWED),
                'data' => []
            ]);
        }

        // 限流异常
        if ($e instanceof ThrottleRequestsException) {
            return response()->json([
                'code' => ResponseCode::TOO_MANY_REQUESTS,
                'msg' => ResponseCode::getMessage(ResponseCode::TOO_MANY_REQUESTS),
                'data' => []
            ]);
        }

        // 文件过大异常
        if ($e instanceof PostTooLargeException) {
            return response()->json([
                'code' => ResponseCode::INVALID_PARAMS,
                'msg' => '上传文件过大，请检查文件大小限制',
                'data' => []
            ]);
        }

        // 数据库异常
        if ($e instanceof QueryException) {
            $message = config('app.debug')
                ? $e->getMessage()
                : ResponseCode::getMessage(ResponseCode::DATABASE_ERROR);

            return response()->json([
                'code' => ResponseCode::DATABASE_ERROR,
                'msg' => $message,
                'data' => []
            ]);
        }

        // 其他异常
        $code = ResponseCode::SERVER_ERROR;
        $message = config('app.debug')
            ? $e->getMessage()
            : ResponseCode::getMessage(ResponseCode::SERVER_ERROR);

        return response()->json([
            'code' => $code,
            'msg' => $message,
            'data' => []
        ]);
    }

    /**
     * 处理 App 端 API 异常
     * 禁止暴露业务细节，使用通用错误提示
     */
    protected function handleAppApiException($request, Throwable $e)
    {
        // App 端自定义业务异常
        if ($e instanceof AppApiException) {
            return response()->json([
                'code' => $e->getCode(),
                'msg' => $e->getMessage(),
                'data' => $e->getData(),
            ]);
        }

        // 验证异常
        if ($e instanceof ValidationException) {
            return response()->json([
                'code' => AppResponseCode::INVALID_PARAMS,
                'msg' => $e->validator->errors()->first(),
                'data' => []
            ]);
        }

        // 认证异常
        if ($e instanceof AuthenticationException) {
            return response()->json([
                'code' => AppResponseCode::UNAUTHORIZED,
                'msg' => AppResponseCode::getMessage(AppResponseCode::UNAUTHORIZED),
                'data' => []
            ]);
        }

        // 授权异常
        if ($e instanceof AuthorizationException) {
            return response()->json([
                'code' => AppResponseCode::FORBIDDEN,
                'msg' => AppResponseCode::getMessage(AppResponseCode::FORBIDDEN),
                'data' => []
            ]);
        }

        // 模型未找到异常 - 不暴露具体信息
        if ($e instanceof ModelNotFoundException) {
            return response()->json([
                'code' => AppResponseCode::DATA_NOT_FOUND,
                'msg' => AppResponseCode::getMessage(AppResponseCode::DATA_NOT_FOUND),
                'data' => [],
            ]);
        }

        // 404 异常
        if ($e instanceof NotFoundHttpException) {
            return response()->json([
                'code' => AppResponseCode::NOT_FOUND,
                'msg' => AppResponseCode::getMessage(AppResponseCode::NOT_FOUND),
                'data' => []
            ]);
        }

        // 方法不允许异常 - 记录日志但返回通用错误
        if ($e instanceof MethodNotAllowedHttpException) {
            Log::error('App端方法不允许异常', ['content' => $e->getMessage()]);
            return response()->json([
                'code' => AppResponseCode::NOT_FOUND,
                'msg' => AppResponseCode::getMessage(AppResponseCode::NOT_FOUND),
                'data' => []
            ]);
        }

        // 限流异常
        if ($e instanceof ThrottleRequestsException) {
            return response()->json([
                'code' => AppResponseCode::TOO_MANY_REQUESTS,
                'msg' => AppResponseCode::getMessage(AppResponseCode::TOO_MANY_REQUESTS),
                'data' => []
            ]);
        }

        // 文件过大异常
        if ($e instanceof PostTooLargeException) {
            return response()->json([
                'code' => AppResponseCode::INVALID_PARAMS,
                'msg' => '上传文件过大',
                'data' => []
            ]);
        }

        // 数据库异常 - 记录详细日志，返回通用错误
        if ($e instanceof QueryException) {
            Log::error('App端数据库异常', [
                'error' => $e->getMessage(),
                'url' => $request->fullUrl(),
            ]);
            return response()->json([
                'code' => AppResponseCode::SERVER_ERROR,
                'msg' => AppResponseCode::getMessage(AppResponseCode::SERVER_ERROR),
                'data' => []
            ]);
        }

        // 其他异常 - 记录详细日志，返回通用错误
        Log::error('App端未知异常', [
            'exception' => get_class($e),
            'error' => $e->getMessage(),
            'url' => $request->fullUrl(),
        ]);

        return response()->json([
            'code' => AppResponseCode::SERVER_ERROR,
            'msg' => AppResponseCode::getMessage(AppResponseCode::SERVER_ERROR),
            'data' => []
        ]);
    }

    /**
     * 转换认证异常为未授权响应
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            // App 端使用 AppResponseCode
            if ($this->isAppRequest($request)) {
                return response()->json([
                    'code' => AppResponseCode::UNAUTHORIZED,
                    'msg' => AppResponseCode::getMessage(AppResponseCode::UNAUTHORIZED),
                    'data' => [],
                ]);
            }

            // Admin/System 端使用 ResponseCode
            return response()->json([
                'code' => ResponseCode::UNAUTHORIZED,
                'msg' => ResponseCode::getMessage(ResponseCode::UNAUTHORIZED),
                'data' => [],
            ]);
        }

        return redirect()->guest($exception->redirectTo() ?? route('login'));
    }

    /**
     * Log exception with context information
     */
    protected function logExceptionWithContext(Throwable $e): void
    {
        // Skip logging for certain exception types
        if ($e instanceof ValidationException ||
            $e instanceof AuthenticationException ||
            $e instanceof AuthorizationException ||
            $e instanceof ApiException ||
            $e instanceof AppApiException) {
            return;
        }

        $context = [
            'message' => $e->getMessage(),
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'ip' => request()->ip(),
            'user_id' => request()->user() ? request()->user()->user_id : 0,
            'user_name' => request()->user() ? request()->user()->nick_name : 'unknown',
            'is_app_request' => $this->isAppRequest(request()),
        ];

        // Add request data for non-GET requests
        if (!request()->isMethod('GET')) {
            $context['request_data'] = request()->except(['password', 'password_confirmation']);
        }

        Log::error('Exception occurred', $context);
    }
}
