<?php

declare(strict_types=1);

namespace App\Http\Controllers\Complaint;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResponse;
use App\Services\Complaint\ComplaintEmailService;
use Illuminate\Http\Request;

/**
 *
 */
class ComplaintEmailController extends Controller
{
    public function getEmailList(Request $request): \Illuminate\Http\JsonResponse
    {
        $list = collect(ComplaintEmailService::ENTERPRISE_EMAIL_LIST)->pluck('email')
            ->values()
            ->toArray();
        return ApiResponse::success(['data' => $list]);
    }
}
