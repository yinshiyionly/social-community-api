<?php

declare(strict_types=1);

namespace App\Http\Controllers\Complaint;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 *
 */
class ComplaintEmailListController extends Controller
{
    public function getEmailList(Request $request): array
    {
        return [
            'jubao@12377.cn',
            'qinqurn@bytedance.com',
            'lcz7610@126.com'
        ];
    }
}
