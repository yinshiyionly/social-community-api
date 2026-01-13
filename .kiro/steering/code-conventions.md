---
inclusion: always
---

# PHP Code Conventions

## 技术栈

- PHP 7.4.33（严格限制，禁止使用 8.0+ 语法）
- PostgreSQL 17.6
- Redis 7.2
- Laravel 8.x (Web 框架)
- Eloquent (ORM)
- Monolog (日志)

## PHP 版本限制（重要）

项目固定使用 PHP 7.4.33，**严禁使用 PHP 8.0+ 的语法特性**：

```php
// ❌ 禁止使用 - PHP 8.0+ 语法
$value = $obj?->property;           // Nullsafe operator
$result = match($x) { ... };        // Match expression
fn($x) => $x * 2;                   // 箭头函数（7.4可用但建议谨慎）
#[Attribute]                        // Attributes
public function foo(): mixed {}     // mixed 类型
$arr = [...$arr1, ...$arr2];        // Array spread (部分场景)
throw new Exception() ?: null;      // throw 表达式

// ✅ 正确写法 - PHP 7.4 兼容
$value = $obj ? $obj->property : null;
$value = isset($obj) ? $obj->property : null;
$value = optional($obj)->property;  // Laravel helper
```

### 常见替代写法

```php
// Nullsafe 替代
// ❌ $this->create_time?->format('Y-m-d')
// ✅ 
$this->create_time ? $this->create_time->format('Y-m-d') : null

// 箭头函数替代（复杂逻辑时）
// ❌ fn() => new DeptResource($this->dept)
// ✅ 
function () {
    return new DeptResource($this->dept);
}

// Constructor property promotion 不可用
// ❌ public function __construct(private string $name) {}
// ✅ 
private string $name;
public function __construct(string $name) {
    $this->name = $name;
}
```

## 核心设计原则

- 单一职责：每个类只负责一个功能领域
- 依赖注入：通过构造函数或方法注入依赖
- 无状态服务：Service 类不保存请求状态
- 隔离响应：App 模块使用 AppApiResponse 类返回，Admin 模块使用 ApiResponse 类返回

## Architecture Pattern

API 开发路线：

```
Route → Request (验证) → Controller → Service → Model/DB → Resource (格式化)
```

## 命名规范

### 类命名

| 类型 | 规范 | 示例 |
|-----|-----|-----|
| 模型 | 模块前缀 + 单数 | `SystemUser`, `AppAdItem` |
| 控制器 | 资源名 + Controller | `UserController`, `AuthController` |
| 服务类 | 功能名 + Service | `FileUploadService`, `SmsVerificationService` |
| 资源类 | 模型名 + Resource | `UserResource`, `DeptResource` |
| 请求类 | 动作 + 资源 + Request | `LoginRequest`, `UserStoreRequest` |
| 中间件 | 模块 + 功能 | `AdminJwtAuth`, `AppJwtAuth` |
| 常量类 | 模块 + ResponseCode | `ResponseCode`, `AppResponseCode` |

### 方法命名

```php
// 控制器方法
public function index()    // 列表
public function show($id)  // 详情
public function store()    // 创建
public function update()   // 更新
public function destroy()  // 删除

// 模型作用域
public function scopeOnline($query)     // 查询作用域
public function scopeBySpace($query, $id)

// 关联关系
public function adSpace()   // belongsTo
public function items()     // hasMany
```

## 模型规范

```php
<?php

namespace App\Models\App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppAdItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'app_ad_item';
    protected $primaryKey = 'ad_id';

    protected $fillable = [
        'space_id',
        'ad_title',
        // ...
    ];

    protected $casts = [
        'ad_id' => 'integer',
        'ext_json' => 'array',
        'start_time' => 'datetime',
    ];

    // 状态常量
    const STATUS_ONLINE = 1;
    const STATUS_OFFLINE = 2;

    // 关联关系
    public function adSpace()
    {
        return $this->belongsTo(AppAdSpace::class, 'space_id', 'space_id');
    }

    // 查询作用域
    public function scopeOnline($query)
    {
        return $query->where('status', self::STATUS_ONLINE);
    }
}
```

## 控制器规范

```php
<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Http\Requests\System\LoginRequest;
use App\Http\Resources\ApiResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    /**
     * 登录
     */
    public function login(LoginRequest $request)
    {
        // 业务逻辑...
        return ApiResponse::success(['token' => $token], '操作成功');
    }

    /**
     * 获取用户信息
     */
    public function getInfo(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return ApiResponse::error('未登录');
        }
        return ApiResponse::success([
            'user' => new UserResource($user),
        ], '操作成功');
    }
}
```

## 请求验证规范

```php
<?php

namespace App\Http\Requests\System;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'username' => 'required|string|max:20',
            'password' => 'required|string|max:30',
            'code' => 'required|string|max:10',
        ];
    }

    public function messages()
    {
        return [
            'username.required' => '用户名不能为空。',
            'password.required' => '密码不能为空。',
        ];
    }
}
```

## API 响应规范

**重要：不同模块使用不同的响应类**

| 模块 | 响应类 | 用途 | 错误信息策略 |
|-----|-------|-----|------------|
| Admin | `ApiResponse` | 后台管理 | 可暴露详细错误 |
| System | `ApiResponse` | 系统管理 | 可暴露详细错误 |
| App | `AppApiResponse` | C端用户 | **禁止暴露业务细节** |

### Admin/System 端响应 (ApiResponse)

用于后台管理，可以返回详细的错误信息便于调试：

```php
use App\Http\Resources\ApiResponse;

// 成功响应
return ApiResponse::success(['token' => $token], '操作成功');

// 分页响应
return ApiResponse::paginate($paginator, UserListResource::class);

// 列表响应
return ApiResponse::collection($data, UserListResource::class);

// 单个资源
return ApiResponse::resource($user, UserResource::class);

// 错误响应 - 可以包含详细信息
return ApiResponse::error('用户名已被占用');
return ApiResponse::error('部门下存在子部门，无法删除');
return ApiResponse::unauthorized('请登录后操作');
return ApiResponse::tokenExpired('Token已过期');
return ApiResponse::forbidden('无权访问该资源');
return ApiResponse::notFound('用户不存在');
```

### App 端响应 (AppApiResponse)

用于 C 端用户，**严禁暴露业务错误细节**，使用通用错误提示：

```php
use App\Http\Resources\App\AppApiResponse;

// 成功响应
return AppApiResponse::success(['data' => $data]);

// 分页响应
return AppApiResponse::paginate($paginator, ItemListResource::class);

// 游标分页（无限滚动）
return AppApiResponse::cursorPaginate($paginator, ItemListResource::class);

// 错误响应 - 使用通用提示，不暴露细节
// ❌ 错误示范
return AppApiResponse::error('用户ID:12345不存在');
return AppApiResponse::error('数据库连接失败');
return AppApiResponse::error('Redis缓存异常');

// ✅ 正确示范
return AppApiResponse::error('操作失败，请稍后重试');
return AppApiResponse::unauthorized('请先登录');
return AppApiResponse::tokenExpired('登录已过期，请重新登录');
return AppApiResponse::accountDisabled('账号已被禁用');
return AppApiResponse::dataNotFound('内容不存在');
return AppApiResponse::serverError('服务器繁忙，请稍后重试');
```

### App 端错误处理原则

```php
// 在 App 控制器中
class ArticleController extends Controller
{
    public function show($id)
    {
        try {
            $article = Article::findOrFail($id);
            return AppApiResponse::resource($article, ArticleResource::class);
        } catch (ModelNotFoundException $e) {
            // ❌ 不要暴露具体信息
            // return AppApiResponse::error('文章ID:' . $id . '不存在');
            
            // ✅ 使用通用提示
            return AppApiResponse::dataNotFound('内容不存在');
        } catch (\Exception $e) {
            // 记录详细日志，但返回通用错误
            Log::error('获取文章失败', [
                'article_id' => $id,
                'error' => $e->getMessage(),
            ]);
            return AppApiResponse::serverError();
        }
    }
}
```

### 响应格式

```json
// 成功
{
    "code": 200,
    "msg": "操作成功",
    "data": {}
}

// 分页
{
    "code": 200,
    "msg": "查询成功",
    "total": 100,
    "rows": []
}

// 游标分页（App端）
{
    "code": 200,
    "msg": "查询成功",
    "data": {
        "list": [],
        "next_cursor": "xxx",
        "has_more": true
    }
}

// 错误
{
    "code": 6000,
    "msg": "操作失败"
}
```

## Resource 资源类规范

列表和详情使用独立的 Resource 类，避免字段混杂：

### 目录结构

```
app/Http/Resources/
├── System/
│   ├── UserResource.php          # 详情（完整字段）
│   ├── UserListResource.php      # 列表（精简字段）
│   └── UserSimpleResource.php    # 关联引用（最小字段）
└── App/
    ├── MemberResource.php
    ├── MemberListResource.php
    └── MemberSimpleResource.php
```

### 列表 Resource（精简字段）

```php
<?php

namespace App\Http\Resources\System;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 用户列表资源 - 用于列表展示
 */
class UserListResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'userId' => $this->user_id,
            'userName' => $this->user_name,
            'nickName' => $this->nick_name,
            'status' => $this->status,
            'deptName' => $this->dept ? $this->dept->dept_name : null,
            'createTime' => $this->create_time ? $this->create_time->format('Y-m-d H:i:s') : null,
        ];
    }
}
```

### 详情 Resource（完整字段）

```php
<?php

namespace App\Http\Resources\System;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 用户详情资源 - 用于详情/编辑页面
 */
class UserResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            // 基础字段
            'userId' => $this->user_id,
            'deptId' => $this->dept_id,
            'userName' => $this->user_name,
            'nickName' => $this->nick_name,
            'email' => $this->email,
            'phonenumber' => $this->phonenumber,
            'sex' => $this->sex,
            'avatar' => $this->avatar,
            'status' => $this->status,
            'remark' => $this->remark,
            
            // 日期字段（PHP 7.4 兼容写法）
            'createTime' => $this->create_time ? $this->create_time->format('Y-m-d H:i:s') : null,
            'updateTime' => $this->update_time ? $this->update_time->format('Y-m-d H:i:s') : null,
            'loginDate' => $this->login_date ? $this->login_date->toISOString() : null,
            
            // 关联数据（详情才加载）
            'dept' => $this->whenLoaded('dept', function () {
                return new DeptSimpleResource($this->dept);
            }),
            'roles' => $this->whenLoaded('roles', function () {
                return RoleSimpleResource::collection($this->roles);
            }),
            'posts' => $this->whenLoaded('posts', function () {
                return PostSimpleResource::collection($this->posts);
            }),
            
            // 编辑表单需要的 ID 数组
            'roleIds' => $this->when(isset($this->roleIds), $this->roleIds),
            'postIds' => $this->when(isset($this->postIds), $this->postIds),
        ];
    }
}
```

### 简单 Resource（关联引用）

```php
<?php

namespace App\Http\Resources\System;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 用户简单资源 - 用于其他资源的关联引用
 */
class UserSimpleResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'userId' => $this->user_id,
            'userName' => $this->user_name,
            'nickName' => $this->nick_name,
        ];
    }
}
```

### 控制器中使用

```php
class UserController extends Controller
{
    // 列表 - 使用 ListResource
    public function index(Request $request)
    {
        $users = SystemUser::with('dept')->paginate();
        return ApiResponse::paginate($users, UserListResource::class);
    }

    // 详情 - 使用完整 Resource
    public function show($id)
    {
        $user = SystemUser::with(['dept', 'roles', 'posts'])->findOrFail($id);
        return ApiResponse::resource($user, UserResource::class);
    }

    // 下拉选项 - 使用 SimpleResource
    public function options()
    {
        $users = SystemUser::select(['user_id', 'user_name', 'nick_name'])->get();
        return ApiResponse::collection($users, UserSimpleResource::class);
    }
}
```

### 命名约定

| 场景 | 命名 | 用途 |
|-----|-----|-----|
| 详情 | `{Model}Resource` | 详情页、编辑页 |
| 列表 | `{Model}ListResource` | 列表页、表格 |
| 引用 | `{Model}SimpleResource` | 下拉选项、关联显示 |

## 服务类规范

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class FileUploadService
{
    /**
     * 上传文件
     *
     * @param UploadedFile $file 上传的文件
     * @param array $options 上传选项
     * @return array 文件信息
     * @throws FileUploadException
     */
    public function upload(UploadedFile $file, array $options = []): array
    {
        // 记录日志
        Log::debug('File upload started', [
            'original_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
        ]);

        try {
            // 业务逻辑...
            return $result;
        } catch (\Exception $e) {
            Log::error('File upload failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
```

## 中间件规范

```php
<?php

namespace App\Http\Middleware\App;

use Closure;
use Illuminate\Http\Request;
use App\Helper\JwtHelper;
use App\Http\Resources\ApiResponse;

class AppJwtAuth
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return ApiResponse::unauthorized('请登录后操作');
        }

        $secret = config('app.jwt_app_secret', config('app.key'));
        $payload = JwtHelper::decode($token, $secret);

        if (!$payload) {
            return ApiResponse::tokenInvalid('Token无效');
        }

        if (JwtHelper::isExpired($payload)) {
            return ApiResponse::tokenExpired('Token已过期');
        }

        // 注入用户标识到 request
        $request->attributes->set('member_id', $payload['member_id']);
        $request->attributes->set('jwt_payload', $payload);

        return $next($request);
    }
}
```

## 常量定义规范

```php
<?php

namespace App\Constant;

class AppResponseCode
{
    // 成功
    const SUCCESS = 200;
    const SUCCESS_MSG = '操作成功';

    // 认证相关 (401x)
    const UNAUTHORIZED = 4010;
    const TOKEN_INVALID = 4011;
    const TOKEN_EXPIRED = 4012;

    // 权限相关 (403x)
    const FORBIDDEN = 4030;
    const ACCOUNT_DISABLED = 4031;

    // 业务错误 (600x)
    const BUSINESS_ERROR = 6000;

    /**
     * 获取状态码对应的消息
     */
    public static function getMessage(int $code): string
    {
        // ...
    }
}
```

## 异常处理规范

```php
// 自定义异常放在 app/Exceptions/{Module}/ 目录
// 例如：app/Exceptions/FileUpload/FileUploadException.php

namespace App\Exceptions\FileUpload;

class FileUploadException extends \Exception
{
    // ...
}

class FileSizeExceededException extends FileUploadException
{
    // ...
}
```

## 代码风格

- 使用 PSR-12 代码风格
- 类属性和方法使用 camelCase
- 数据库字段使用 snake_case
- 常量使用 UPPER_SNAKE_CASE
- 每个文件只包含一个类
- 使用类型声明（PHP 7.4+）
- 方法添加 PHPDoc 注释

## 注释规范

```php
/**
 * 类描述
 */
class MyClass
{
    /**
     * 方法描述
     *
     * @param string $param 参数说明
     * @return array 返回值说明
     * @throws MyException 异常说明
     */
    public function myMethod(string $param): array
    {
        // 单行注释
        
        /*
         * 多行注释
         */
    }
}
```

## 日志规范

```php
use Illuminate\Support\Facades\Log;

// 调试信息
Log::debug('Debug message', ['context' => $data]);

// 一般信息
Log::info('Info message', ['user_id' => $userId]);

// 警告
Log::warning('Warning message', ['error' => $message]);

// 错误
Log::error('Error message', [
    'exception' => $e->getMessage(),
    'trace' => $e->getTraceAsString(),
]);
```

### Job 队列日志规范

**Job 中的日志必须指定 `job` channel**，便于独立查看和排查队列问题：

```php
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $userId;
    private $message;

    public function __construct(int $userId, string $message)
    {
        $this->userId = $userId;
        $this->message = $message;
    }

    public function handle()
    {
        // ✅ 正确：使用 job channel
        Log::channel('job')->info('开始发送通知', [
            'job' => self::class,
            'user_id' => $this->userId,
        ]);

        try {
            // 业务逻辑...
            
            Log::channel('job')->info('通知发送成功', [
                'job' => self::class,
                'user_id' => $this->userId,
            ]);
        } catch (\Exception $e) {
            Log::channel('job')->error('通知发送失败', [
                'job' => self::class,
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw $e; // 重新抛出以触发重试
        }
    }

    /**
     * 任务失败处理
     */
    public function failed(\Throwable $exception)
    {
        Log::channel('job')->error('任务最终失败', [
            'job' => self::class,
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
        ]);
    }
}
```

### Job 日志上下文规范

```php
// Job 日志必须包含以下上下文
Log::channel('job')->info('日志消息', [
    'job' => self::class,           // 必须：Job 类名
    'attempt' => $this->attempts(), // 可选：当前重试次数
    // 业务相关字段...
]);

// ❌ 错误：不指定 channel
Log::info('Job执行中...');

// ✅ 正确：指定 job channel
Log::channel('job')->info('Job执行中...');
```
