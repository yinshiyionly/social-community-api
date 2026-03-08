<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Services\App\LiveCallbackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LiveCallbackController extends Controller
{
    /**
     * 百家云直播回调
     *
     * @param Request $request
     * @param LiveCallbackService $service
     * @return JsonResponse
     */
    public function baijiayun(Request $request, LiveCallbackService $service): JsonResponse
    {
        $result = $service->handleBaijiayun((array)$request->all());

        return response()->json($result);
    }
}
