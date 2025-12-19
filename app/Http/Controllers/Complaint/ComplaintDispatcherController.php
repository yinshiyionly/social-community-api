<?php

declare(strict_types=1);

namespace App\Http\Controllers\Complaint;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use App\Http\Controllers\Complaint\ComplaintEnterpriseController;
use App\Http\Controllers\Complaint\PoliticsController;
use App\Http\Controllers\Complaint\DefamationController;

class ComplaintDispatcherController extends Controller
{
    /**
     * 映射关系表：将 type 参数映射到对应的控制器和方法
     */
    protected $map = [
        'enterprise' => [ComplaintEnterpriseController::class, 'create'],
        'politics'   => [PoliticsController::class, 'create'],
        'defamation' => [DefamationController::class, 'create'],
    ];

    public function dispatchRequest(Request $request)
    {
        $type = $request->input('type');

        // 1. 验证类型是否存在
        if (!array_key_exists($type, $this->map)) {
            return response()->json(['error' => '无效的投诉类型'], 400);
        }

        [$controllerClass, $method] = $this->map[$type];

        // 2. 动态实例化控制器并调用方法
        // 使用 App::call 可以确保 Laravel 自动注入控制器构造函数中的依赖
        return App::call([App::make($controllerClass), $method]);
    }
}
