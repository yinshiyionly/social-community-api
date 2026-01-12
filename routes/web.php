<?php

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
        \App\Models\System\SystemMenu::query()->get()->toArray()
    );
});
