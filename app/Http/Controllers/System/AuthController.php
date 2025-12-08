<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Http\Requests\System\LoginRequest;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\System\UserResource;
use App\Models\System\SystemLogininfor;
use App\Models\System\SystemMenu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Mews\Captcha\Facades\Captcha;

class AuthController extends Controller
{
    /**
     * 登录
     */
    public function login(LoginRequest $request)
    {
        $uuid = $request->input('uuid');
        $code = $request->input('code');

        $cacheCaptcha = Cache::pull('captcha:' . $uuid);
        // 验证码是否存在
        if (empty($cacheCaptcha)) {
            return response()->json(['msg' => '验证码错误', 'code' => 500], 200);
        }
        // 验证码正确性
        if (!captcha_api_check($code, $cacheCaptcha, 'math')) {
            /*{
                "msg": "验证码错误",
                "code": 500
            }*/
            return response()->json(['msg' => '验证码错误', 'code' => 500], 200);

        }


        $credentials = [
            'user_name' => $request->username,
            'password' => $request->password
        ];

        if (Auth::attempt($credentials)) {
            $user = $request->user();;

            // 检查用户状态
            if ($user->status != '0') {
                return ApiResponse::error('用户已被停用，请联系管理员');
            }

            // 更新登录信息
            $user->update([
                'login_ip' => $request->ip(),
                'login_date' => now()
            ]);

            // 生成token
            $token = $user->createToken('auth_token')->plainTextToken;

            // 记录登录成功日志
            SystemLogininfor::recordLoginSuccess($user->user_name, $request);

            return ApiResponse::success(['token' => $token], '操作成功');
        }

        // 记录登录失败日志
        SystemLogininfor::recordLoginFail($request->username, $request, '用户名或密码错误');

        return ApiResponse::error('用户名或密码错误');
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

        // 获取用户角色和权限
        $roles = $user->getRoleKeys();
        $permissions = $user->getPermissions();

        return ApiResponse::success([
            'user' => new UserResource($user->load(['dept', 'roles', 'posts'])),
            'roles' => $roles,
            'permissions' => array_values($permissions),
// todo admin 权限
//        'permissions' => ['*:*:*']
        ], '操作成功');
    }

    /**
     * 获取路由信息
     */
    public function getRouters(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return ApiResponse::error('未登录');
        }

        // 获取用户菜单权限
        if ($user->isAdmin()) {
            // 管理员获取所有菜单，只获取目录(M)和菜单(C)类型，排除按钮(F)
            $menus = SystemMenu::where('status', '0')
                ->whereIn('menu_type', ['M', 'C'])
                ->orderBy('parent_id')
                ->orderBy('order_num')
                ->get([
                    'menu_id', 'menu_name', 'parent_id', 'order_num', 'path',
                    'component', 'query', 'is_frame', 'is_cache', 'menu_type',
                    'visible', 'status', 'perms', 'icon', 'remark'
                ])
                ->toArray();
        } else {
            // 普通用户获取角色菜单，只获取目录(M)和菜单(C)类型，排除按钮(F)
            $menuIds = [];
            foreach ($user->roles as $role) {
                $menuIds = array_merge($menuIds, $role->getMenuIds());
            }
            $menuIds = array_unique($menuIds);

            $menus = SystemMenu::whereIn('menu_id', $menuIds)
                ->where('status', '0')
                ->whereIn('menu_type', ['M', 'C'])
                ->orderBy('parent_id')
                ->orderBy('order_num')
                ->get([
                    'menu_id', 'menu_name', 'parent_id', 'order_num', 'path',
                    'component', 'query', 'is_frame', 'is_cache', 'menu_type',
                    'visible', 'status', 'perms', 'icon', 'remark'
                ])
                ->toArray();
        }

        // 构建菜单树（使用数组方式）
        $menuTree = $this->buildMenuTreeArray($menus);

        // 转换为路由格式
        $routers = $this->buildRoutersArray($menuTree);

        return ApiResponse::success(['data' => $routers], '操作成功');
    }

    /**
     * 构建菜单树（数组方式）
     */
    private function buildMenuTreeArray($menus, $parentId = 0)
    {
        $tree = [];

        foreach ($menus as $menu) {
            if ($menu['parent_id'] == $parentId) {
                // 递归查找子菜单
                $children = $this->buildMenuTreeArray($menus, $menu['menu_id']);
                if (!empty($children)) {
                    $menu['children'] = $children;
                }
                $tree[] = $menu;
            }
        }

        return $tree;
    }

    /**
     * 构建前端路由（数组方式）
     */
    private function buildRoutersArray($menus)
    {
        $routers = [];

        foreach ($menus as $menu) {
            // 页面组件规则
            // 菜单->目录使用 Layout
            // 菜单->目录(非一级目录) 使用 ParentView
            // 菜单->菜单 使用数据表字段 component
            $component = $menu['component'] ?: 'Layout';
            if ($menu['parent_id'] !=0 && empty($menu['component'])) {
                $component = 'ParentView';
            }
            $router = [
                'name' => ucfirst($menu['path']), // 首字母大写，符合RuoYi规范
                'path' => $menu['parent_id'] == 0 ? '/' . $menu['path'] : $menu['path'],
                'hidden' => $menu['visible'] == '1',
                // 'component' => $menu['component'] ?: 'Layout',
                'component' => $component,
                'meta' => [
                    'title' => $menu['menu_name'],
                    'icon' => $menu['icon'],
                    'noCache' => $menu['is_cache'] == '1',
                    'link' => $menu['is_frame'] == '0' ? $menu['path'] : null
                ]
            ];

            // 如果是目录类型(M)且有子菜单，添加redirect和alwaysShow
            if ($menu['menu_type'] == 'M') {
                // system_menu 数据表中的 visible 字段控制
                // `visible` char(1) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0' COMMENT '菜单状态（0显示 1隐藏）'
                // $router['alwaysShow'] = true;
                $router['alwaysShow'] = $menu['visible'] == 0;

                // system_menu 数据表中的 is_frame 字段控制
                // `is_frame` int(11) NOT NULL DEFAULT '1' COMMENT '是否为外链（0是 1否）'
                if ($menu['is_frame'] ==1) {
                    $router['redirect'] = 'noRedirect';
                } else {
                    // 外链是完整的 path 路径
                    $router['path'] = $menu['path'];
                }

            }

            if (!empty($menu['query'])) {
                $router['query'] = $menu['query'];
            }

            // 处理子菜单
            if (!empty($menu['children'])) {
                $router['children'] = $this->buildRoutersArray($menu['children']);
            }

            $routers[] = $router;
        }

        return $routers;
    }

    /**
     * 退出登录
     */
    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        return ApiResponse::success([], '退出成功');
    }

    /**
     * 获取验证码
     */
    public function captchaImage(Request $request)
    {

        $uuid = Str::uuid()->toString();

        // 生成验证码图片和内容
        $captcha = Captcha::create('math', true); // 第二个参数 true 返回 base64

        $code = $captcha['key']; // 验证码文本
        $img = $captcha['img']; // base64 图片
        $img = preg_replace('/^data:image\/\w+;base64,/', '', $img);

        // 将验证码存入缓存，设置过期时间 5 分钟
        Cache::put('captcha:' . $uuid, $code, now()->addMinutes(5));
        return response()->json([
            'msg' => '操作成功',
            'img' => $img,
            'uuid' => $uuid,
            'code' => 200,
            'captchaEnabled' => true,
        ]);

        /*{
            "msg": "操作成功",
            "img": "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",
            "code": 200,
            "captchaEnabled": true,
            "uuid": "7f8d64c718f34753a82d2a25f5fc761a"
        }*/
    }
}
