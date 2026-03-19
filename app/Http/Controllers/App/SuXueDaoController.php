<?php

namespace App\Http\Controllers\App;

use App\Helper\SuXueDaoHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\App\AppApiResponse;
use App\Models\App\AppMemberBase;
use App\Models\SuXueDao\V5User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SuXueDaoController extends Controller
{
    /**
     * 获取速学岛Authorization
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAuthorization(Request $request): \Illuminate\Http\JsonResponse
    {
        // 1. 获取速学岛App token中的 member_id
        $memberId = (int)$request->attributes->get('member_id');
        // 2. 使用 member_id 去 app_member_base 中查询手机号
        $memberInfo = AppMemberBase::query()
            ->select(['member_id', 'phone'])
            ->where('member_id', $memberId)
            ->first();

        // 3. 判断用户是否存在
        if (empty($memberInfo) || empty($memberInfo['phone'])) {
            return AppApiResponse::success([
                'data' => [
                    'aiToken' => ''
                ]
            ]);
            return AppApiResponse::error('用户不存在');
        }
        try {
            // 4. 从 v5_user 数据表中用 mobile 查询用户主键ID
            $v5UserInfo = V5User::query()
                ->select(['id', 'uid', 'mobile'])
                ->where('mobile', $memberInfo['phone'])
                ->first();

            // 5. 判断用户是否两端存在
            if (empty($v5UserInfo) || empty($v5UserInfo['id'])) {
                return AppApiResponse::success([
                    'data' => [
                        'aiToken' => ''
                    ]
                ]);
                return AppApiResponse::error('用户未注册AI工具站或未打通用户数据');
            }

            // 2. 用主键ID调用 SuXueDaoHelper 生成 Authorization
            $authString = $v5UserInfo['id'] . '|' . time();
            $authorization = SuXueDaoHelper::authcode($authString, 'ENCODE');

            // 3. 返回 Authorization
            return AppApiResponse::success([
                'data' => [
                    'aiToken' => $authorization ?? ''
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('获取速学岛Authorization失败', [
                'mobile' => $memberInfo['phone'] ?? null,
                'error'  => $e->getMessage()
            ]);
            return AppApiResponse::serverError();
        }
    }
}
