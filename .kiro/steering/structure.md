# 项目结构与编码规范

## 目录结构

```
app/
├── Console/Commands/       # Artisan 命令
├── Constant/               # 常量定义 (ResponseCode 等)
├── Exceptions/             # 异常类 (按模块分组)
├── Helper/                 # 工具类 (JwtHelper, DatetimeHelper)
├── Http/
│   ├── Controllers/
│   │   ├── System/         # System 模块控制器
│   │   └── App/            # App 模块控制器 (待创建)
│   ├── Middleware/
│   │   ├── Admin/          # Admin 端中间件 (AdminJwtAuth)
│   │   ├── App/            # App 端中间件 (AppJwtAuth)
│   │   └── System/         # System 端中间件
│   ├── Requests/           # 表单验证 (按模块分组)
│   └── Resources/          # API 资源类 (按模块分组)
├── Models/
│   ├── System/             # System 模块模型
│   └── App/                # App 模块模型
├── Services/               # 无状态业务服务类
└── Providers/              # 服务提供者

routes/
├── admin.php               # Admin 路由入口
├── admin/                  # Admin 子模块路由
│   └── system.php
├── app.php                 # App 路由入口
├── app/                    # App 子模块路由
└── file.php                # 文件上传路由
```

## 请求处理流程

```
Route → Request (验证) → Controller → Service → Resource (格式化)
```

## 编码规范

### 命名约定

| 类型 | 规范 | 示例 |
|-----|-----|-----|
| 模型 | 模块前缀 + 单数 | `SystemUser`, `AppAdItem` |
| 控制器 | 资源名 + Controller | `UserController`, `AuthController` |
| 服务类 | 功能名 + Service | `FileUploadService` |
| 资源类 | 模型名 + Resource | `UserResource` |
| 请求类 | 动作 + 资源 + Request | `LoginRequest`, `UserStoreRequest` |
| 中间件 | 模块 + 功能 | `AdminJwtAuth`, `AppJwtAuth` |

### 模型规范

```php
class AppAdItem extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $table = 'app_ad_item';
    protected $primaryKey = 'ad_id';
    protected $fillable = [...];
    protected $casts = [...];
    
    // 常量定义
    const STATUS_ONLINE = 1;
    
    // 关联关系
    public function adSpace() { ... }
    
    // 查询作用域
    public function scopeOnline($query) { ... }
}
```

### API 响应规范

使用 `ApiResponse` 统一响应格式：

```php
// 成功
return ApiResponse::success(['token' => $token], '操作成功');

// 分页
return ApiResponse::paginate($paginator, UserResource::class);

// 错误
return ApiResponse::error('错误信息');
return ApiResponse::unauthorized('请登录后操作');
return ApiResponse::tokenExpired('Token已过期');
```

### 中间件注册

在 `app/Http/Kernel.php` 的 `$routeMiddleware` 中注册：

```php
'app.auth' => \App\Http\Middleware\App\AppJwtAuth::class,
'admin.auth' => \App\Http\Middleware\Admin\AdminJwtAuth::class,
'system.auth' => \App\Http\Middleware\System\SystemUserAuth::class,
```

### 新增模块步骤

1. 在 `routes/{module}/` 下创建路由文件（自动加载）
2. 在 `app/Http/Controllers/{Module}/` 创建控制器
3. 在 `app/Http/Middleware/{Module}/` 创建中间件
4. 在 `app/Models/{Module}/` 创建模型
5. 在 `app/Http/Resources/{Module}/` 创建资源类
6. 在 `app/Http/Requests/{Module}/` 创建请求验证类
