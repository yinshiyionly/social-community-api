<?php

use App\Helper\JwtHelper;
use App\Helper\KeywordParser\KeywordRuleParser;
use App\Helper\KeywordParser\Lexer;
use App\Helper\KeywordParser\Parser;
use App\Helper\KeywordParser\QueryBuilder;
use App\Helper\Volcengine\InsightAPI;
use App\Http\Controllers\PublicRelation\MaterialEnterpriseController;
use App\Jobs\Complaint\ComplaintEnterpriseSendMailJob;
use App\Jobs\Detection\Task\DetectionTaskWarnJob;
use App\Mail\Detection\Task\DetectionTaskWarnMail;
use App\Models\Insight\InsightPost;
use App\Services\Complaint\ComplaintEmailService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    dd(
        \App\Models\System\SystemUser::query()->get()->toArray()
    );
    $token = JwtHelper::encode(
        ['member_id' => 1],
        config('app.jwt_app_secret', config('app.key')),
        86400 * 7  // 7天过期
    );
$a = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJtZW1iZXJfaWQiOjEsImlhdCI6MTc2ODIxNzE3NiwiZXhwIjoxNzY4ODIxOTc2fQ.KsBrFgQ1CM14X2heNDbNfOY4zIWcNIfmmeoDaxrchZg";
    $b =JwtHelper::decode($a, config('app.jwt_app_secret'));
    dd(
        $b
    );
});
