<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Resources\App\AppApiResponse;
use App\Http\Resources\App\NewCourseListResource;
use App\Services\App\CourseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CourseController extends Controller
{
    /**
     * @var CourseService
     */
    protected $courseService;

    public function __construct(CourseService $courseService)
    {
        $this->courseService = $courseService;
    }

    /**
     * 获取好课上新列表
     */
    public function newCourses(Request $request)
    {
        $limit = $request->input('limit', 10);

        try {
            $courses = $this->courseService->getNewCourses($limit);

            return AppApiResponse::collection($courses, NewCourseListResource::class);
        } catch (\Exception $e) {
            Log::error('获取好课上新列表失败', [
                'error' => $e->getMessage(),
            ]);

            return AppApiResponse::serverError();
        }
    }
}
