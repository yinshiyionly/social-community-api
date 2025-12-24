<?php

namespace App\Http\Controllers\Mail;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mail\CreateReportEmailRequest;
use App\Http\Requests\Mail\UpdateReportEmailRequest;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\Mail\ReportEmailItemResource;
use App\Http\Resources\Mail\ReportEmailListResource;
use App\Services\Mail\ReportEmailService;
use Illuminate\Http\Request;

/**
 * 举报邮箱配置控制器
 */
class ReportEmailController extends Controller
{
    protected ReportEmailService $service;

    /**
     * 初始化
     *
     * @param ReportEmailService $service
     */
    public function __construct(ReportEmailService $service)
    {
        $this->service = $service;
    }

    /**
     * 列表-分页
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $params = $request->only(['email', 'pageNum', 'pageSize']);
        $paginator = $this->service->list($params);
        return ApiResponse::paginate($paginator, ReportEmailListResource::class);
    }

    /**
     * 创建
     *
     * @param CreateReportEmailRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\ApiException
     */
    public function store(CreateReportEmailRequest $request)
    {
        $this->service->create($request->validated());
        return ApiResponse::created();
    }

    /**
     * 详情
     *
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\ApiException
     */
    public function show($id)
    {
        $item = $this->service->find($id);

        return ApiResponse::resource($item, ReportEmailItemResource::class);
    }

    /**
     * 更新
     *
     * @param UpdateReportEmailRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\ApiException
     */
    public function update(UpdateReportEmailRequest $request)
    {
        $id = $request->get('id', 0);
        $this->service->update($id, $request->validated());
        return ApiResponse::updated();
    }

    /**
     * 删除
     *
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\ApiException
     */
    public function destroy($id)
    {
        $this->service->delete($id);
        return ApiResponse::deleted();
    }

    /**
     * 发送测试邮件
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\ApiException
     */
    public function sendTest(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'receive_email' => 'required|email',
        ], [
            'id.required' => '邮箱配置ID不能为空',
            'id.integer' => '邮箱配置ID必须为整数',
            'receive_email.required' => '收件人邮箱不能为空',
            'receive_email.email' => '收件人邮箱格式不正确',
        ]);

        $id = (int)$request->get('id');
        $receiveEmail = $request->get('receive_email');

        $this->service->sendTest($id, $receiveEmail);

        return ApiResponse::success([], '测试邮件发送成功');
    }
}
