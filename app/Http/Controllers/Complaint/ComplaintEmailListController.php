<?php

declare(strict_types=1);

namespace App\Http\Controllers\Complaint;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResponse;
use Illuminate\Http\Request;

/**
 *
 */
class ComplaintEmailListController extends Controller
{
    public function getEmailList(Request $request): \Illuminate\Http\JsonResponse
    {
        $list = [
            'jubao@12377.cn',
            'qinqurn@bytedance.com',
            'lcz7610@126.com'
        ];
        return ApiResponse::success(['data' => $list]);
    }
}
