<?php

use App\Http\Controllers\System\AuthController;
use App\Http\Controllers\System\ConfigController;
use App\Http\Controllers\System\DeptController;
use App\Http\Controllers\System\DictDataController;
use App\Http\Controllers\System\DictTypeController;
use App\Http\Controllers\System\LogininforController;
use App\Http\Controllers\System\MenuController;
use App\Http\Controllers\System\NoticeController;
use App\Http\Controllers\System\OperlogController;
use App\Http\Controllers\System\PostController;
use App\Http\Controllers\System\RoleController;
use App\Http\Controllers\System\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// 认证相关路由（无需token）
Route::post('/login', [AuthController::class, 'login']);
Route::get('/captchaImage', [AuthController::class, 'captchaImage']);

// 需要认证的路由
Route::middleware('system.auth')->group(function () {
    // 用户信息和路由
    Route::get('/getInfo', [AuthController::class, 'getInfo']);
    Route::get('/getRouters', [AuthController::class, 'getRouters']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // 系统管理
    Route::prefix('system')->group(function () {
        // 用户管理
        Route::prefix('user')->group(function () {
            Route::get('/list', [UserController::class, 'list'])
                ->middleware('permission:system:user:list');
            // /deptTree 会和 /{userId} 冲突，所以将 /deptTree 放在 /{userId} 前面
            Route::get('/deptTree', [UserController::class, 'deptTree']);
            Route::get('/profile', [UserController::class, 'profile']);
            Route::put('/profile', [UserController::class, 'updateProfile']);
            Route::put('/profile/updatePwd', [UserController::class, 'updatePassword']);
            Route::put('/profile/avatar', [UserController::class, 'updateAvatar']);
            Route::get('/{userId}', [UserController::class, 'show'])
                ->middleware('permission:system:user:query');
            Route::get('/', [UserController::class, 'default']);
            Route::post('/', [UserController::class, 'store'])
                ->middleware('permission:system:user:add');
            Route::put('/', [UserController::class, 'update'])
                ->middleware('permission:system:user:edit');
            Route::delete('/{userId}', [UserController::class, 'destroy'])
                ->middleware('permission:system:user:remove');
            Route::put('/resetPwd', [UserController::class, 'resetPwd'])
                ->middleware('permission:system:user:resetPwd');
            Route::put('/changeStatus', [UserController::class, 'changeStatus'])
                ->middleware('permission:system:user:edit');
            Route::get('/authRole/{userId}', [UserController::class, 'getAuthRole'])
                ->middleware('permission:system:user:query');
            Route::put('/authRole', [UserController::class, 'updateAuthRole'])
                ->middleware('permission:system:user:edit');
        });

        // 角色管理
        Route::prefix('role')->group(function () {
            Route::get('/list', [RoleController::class, 'list'])
                ->middleware('permission:system:role:list');
            Route::get('/{roleId}', [RoleController::class, 'show'])
                ->middleware('permission:system:role:query');
            Route::post('/', [RoleController::class, 'store'])
                ->middleware('permission:system:role:add');
            Route::put('/', [RoleController::class, 'update'])
                ->middleware('permission:system:role:edit');
            Route::delete('/{roleId}', [RoleController::class, 'destroy'])
                ->middleware('permission:system:role:remove');
            Route::put('/changeStatus', [RoleController::class, 'changeStatus'])
                ->middleware('permission:system:role:edit');
            Route::put('/dataScope', [RoleController::class, 'dataScope'])
                ->middleware('permission:system:role:edit');
            Route::get('/deptTree/{roleId}', [RoleController::class, 'deptTree']);

            // 角色用户授权管理
            Route::get('/authUser/allocatedList', [RoleController::class, 'allocatedUserList'])
                ->middleware('permission:system:role:list');
            Route::get('/authUser/unallocatedList', [RoleController::class, 'unallocatedUserList'])
                ->middleware('permission:system:role:list');
            Route::put('/authUser/cancel', [RoleController::class, 'authUserCancel'])
                ->middleware('permission:system:role:edit');
            Route::put('/authUser/cancelAll', [RoleController::class, 'authUserCancelAll'])
                ->middleware('permission:system:role:edit');
            Route::put('/authUser/selectAll', [RoleController::class, 'authUserSelectAll'])
                ->middleware('permission:system:role:edit');
        });

        // 菜单管理
        Route::prefix('menu')->group(function () {
            Route::get('/treeselect', [MenuController::class, 'treeselect']);

            Route::get('/list', [MenuController::class, 'list'])
                ->middleware('permission:system:menu:list');
            Route::get('/{menuId}', [MenuController::class, 'show'])
                ->middleware('permission:system:menu:query');
            Route::post('/', [MenuController::class, 'store'])
                ->middleware('permission:system:menu:add');
            Route::put('/', [MenuController::class, 'update'])
                ->middleware('permission:system:menu:edit');
            Route::delete('/{menuId}', [MenuController::class, 'destroy'])
                ->middleware('permission:system:menu:remove');
            Route::get('/roleMenuTreeselect/{roleId}', [MenuController::class, 'roleMenuTreeselect']);
        });

        // 部门管理
        Route::prefix('dept')->group(function () {
            Route::get('/list', [DeptController::class, 'list'])
                ->middleware('permission:system:dept:list');
            Route::get('/{deptId}', [DeptController::class, 'show'])
                ->middleware('permission:system:dept:query');
            Route::post('/', [DeptController::class, 'store'])
                ->middleware('permission:system:dept:add');
            Route::put('/', [DeptController::class, 'update'])
                ->middleware('permission:system:dept:edit');
            Route::delete('/{deptId}', [DeptController::class, 'destroy'])
                ->middleware('permission:system:dept:remove');
            Route::get('/list/exclude/{deptId}', [DeptController::class, 'excludeChild']);
        });

        // 岗位管理
        Route::prefix('post')->group(function () {
            Route::get('/list', [PostController::class, 'list'])
                ->middleware('permission:system:post:list');
            Route::get('/{postId}', [PostController::class, 'show'])
                ->middleware('permission:system:post:query');
            Route::post('/', [PostController::class, 'store'])
                ->middleware('permission:system:post:add');
            Route::put('/', [PostController::class, 'update'])
                ->middleware('permission:system:post:edit');
            Route::delete('/{postIds}', [PostController::class, 'destroy'])
                ->middleware('permission:system:post:remove');
            Route::post('/export', [PostController::class, 'export'])
                ->middleware('permission:system:post:export');
            Route::get('/optionselect', [PostController::class, 'optionselect']);
        });

        // 字典数据管理
        Route::prefix('dict/data')->group(function () {
            Route::get('/list', [DictDataController::class, 'list'])
                ->middleware('permission:system:dict:list');
            Route::get('/type/{dictType}', [DictDataController::class, 'type']);
            Route::post('/export', [DictDataController::class, 'export'])
                ->middleware('permission:system:dict:export');
            Route::get('/{dictCode}', [DictDataController::class, 'show'])
                ->middleware('permission:system:dict:query');
            Route::post('/', [DictDataController::class, 'store'])
                ->middleware('permission:system:dict:add');
            Route::put('/', [DictDataController::class, 'update'])
                ->middleware('permission:system:dict:edit');
            Route::delete('/{dictCodes}', [DictDataController::class, 'destroy'])
                ->middleware('permission:system:dict:remove');
        });

        // 字典类型管理
        Route::prefix('dict/type')->group(function () {
            Route::get('/list', [DictTypeController::class, 'list'])
                ->middleware('permission:system:dict:list');
            Route::get('/{dictId}', [DictTypeController::class, 'show'])
                ->middleware('permission:system:dict:query');
            Route::post('/', [DictTypeController::class, 'store'])
                ->middleware('permission:system:dict:add');
            Route::put('/', [DictTypeController::class, 'update'])
                ->middleware('permission:system:dict:edit');
            Route::delete('/{dictIds}', [DictTypeController::class, 'destroy'])
                ->middleware('permission:system:dict:remove');
            Route::delete('/refreshCache', [DictTypeController::class, 'refreshCache'])
                ->middleware('permission:system:dict:remove');
            Route::get('/optionselect', [DictTypeController::class, 'optionselect']);
            Route::post('/export', [DictTypeController::class, 'export'])
                ->middleware('permission:system:dict:export');
        });

        // 参数配置管理
        Route::prefix('config')->group(function () {
            Route::get('/list', [ConfigController::class, 'list'])
                ->middleware('permission:system:config:list');
            Route::get('/{configId}', [ConfigController::class, 'show'])
                ->middleware('permission:system:config:query');
            Route::get('/configKey/{configKey}', [ConfigController::class, 'configKey']);
            Route::post('/', [ConfigController::class, 'store'])
                ->middleware('permission:system:config:add');
            Route::put('/', [ConfigController::class, 'update'])
                ->middleware('permission:system:config:edit');
            Route::delete('/{configIds}', [ConfigController::class, 'destroy'])
                ->middleware('permission:system:config:remove');
            Route::delete('/refreshCache', [ConfigController::class, 'refreshCache'])
                ->middleware('permission:system:config:remove');
            Route::post('/export', [ConfigController::class, 'export'])
                ->middleware('permission:system:config:export');
        });

        // 通知公告管理
        Route::prefix('notice')->group(function () {
            Route::get('/list', [NoticeController::class, 'list'])
                ->middleware('permission:system:notice:list');
            Route::get('/{noticeId}', [NoticeController::class, 'show'])
                ->middleware('permission:system:notice:query');
            Route::post('/', [NoticeController::class, 'store'])
                ->middleware('permission:system:notice:add');
            Route::put('/', [NoticeController::class, 'update'])
                ->middleware('permission:system:notice:edit');
            Route::delete('/{noticeIds}', [NoticeController::class, 'destroy'])
                ->middleware('permission:system:notice:remove');
            Route::post('/export', [NoticeController::class, 'export'])
                ->middleware('permission:system:notice:export');
        });
    });

    // 系统监控
    Route::prefix('monitor')->group(function () {

        // 操作日志
        Route::prefix('operlog')->group(function () {
            Route::get('/list', [OperlogController::class, 'list'])
                ->middleware('permission:monitor:operlog:list');
            Route::get('/{operId}', [OperlogController::class, 'show'])
                ->middleware('permission:monitor:operlog:query');
            Route::delete('/{operIds}', [OperlogController::class, 'destroy'])
                ->middleware('permission:monitor:operlog:remove');
            Route::delete('/clean', [OperlogController::class, 'clean'])
                ->middleware('permission:monitor:operlog:remove');
            Route::post('/export', [OperlogController::class, 'export'])
                ->middleware('permission:monitor:operlog:export');
        });

        // 登录日志
        Route::prefix('logininfor')->group(function () {
            Route::get('/list', [LogininforController::class, 'list'])
                ->middleware('permission:monitor:logininfor:list');
            Route::delete('/{infoIds}', [LogininforController::class, 'destroy'])
                ->middleware('permission:monitor:logininfor:remove');
            Route::delete('/clean', [LogininforController::class, 'clean'])
                ->middleware('permission:monitor:logininfor:remove');
            Route::get('/unlock/{userName}', [LogininforController::class, 'unlock'])
                ->middleware('permission:monitor:logininfor:unlock');
            Route::post('/export', [LogininforController::class, 'export'])
                ->middleware('permission:monitor:logininfor:export');
        });
    });
});
