<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Repositories\v1\ActivityLogRepository;
use Illuminate\Http\Request;
use App\Http\Resources\v1\ActivityLogResource;

class ActivityLogController extends Controller
{
    private $activityLogRepository;

    /**
     * @param ActivityLogRepository $activityLogRepository
     */
    public function __construct(ActivityLogRepository $activityLogRepository)
    {
        $this->activityLogRepository = $activityLogRepository;
        $this->middleware('permission:activity.view', ['only' => ['index', 'exportActivityLog']]);
    }

    /**
     * @param Request $request
     *
     * @return [json]
     */
    public function index(Request $request)
    {
        $logs = $this->activityLogRepository->index($request->all());
        return ActivityLogResource::collection($logs['logs'])->additional([
            'total_without_filter' => $logs['total_count']
        ]);
    }


    /**
     * @param Request $request
     *
     * @return [type]
     */
    public function exportActivityLog(Request $request)
    {
        $filePath = $this->activityLogRepository->exportActivityLog($request->all());
        return response([
            'file_path' => $filePath,
        ], 200);
    }
}
