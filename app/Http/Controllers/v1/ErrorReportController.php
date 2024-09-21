<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\UpdateStatusErrorReportRequest;
use App\Http\Resources\v1\ErrorReportResource;
use App\Models\Report;
use App\Repositories\v1\ErrorReportRepository;
use Illuminate\Http\Request;
use App\Models\User;

class ErrorReportController extends Controller
{
    private $errorReportRepository;
    /**
     * @param ErrorReportRepository $errorReportRepository
     */
    public function __construct(ErrorReportRepository $errorReportRepository)
    {
        $this->errorReportRepository = $errorReportRepository;
        $this->middleware('permission:error.view', ['only' => ['index', 'exportErrorReport']]);
        $this->middleware('permission:error.update', ['only' => ['show']]);
        $this->middleware('permission:error.resolve', ['only' => ['updateStatus']]);
    }

    /**
     * @param Request $request
     *
     * @return [type]
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $resolvedError = Report::where('status', Report::TYPE_CLOSED)->count();
        $errorReports = $this->errorReportRepository->index($request->all(), $user);
        return (ErrorReportResource::collection($errorReports['errorReport']))
            ->additional(['total_without_filter' => $errorReports['count'],
                'total_resolved_error' => $resolvedError]);
    }

    /**
     * @param report $report
     *
     * @return [json]
     */
    public function show(Report $errorReport)
    {
        $this->authorize('view', User::find($errorReport->user_id));
        return new ErrorReportResource($errorReport);
    }

    /**
     * @param Request $request
     *
     * @return [type]
     */
    public function exportErrorReport(Request $request)
    {
        $filePath = $this->errorReportRepository->exportErrorReport($request->all());
        return response([
            'file_path' => $filePath,
        ], 200);
    }

    /**
     * @param UpdateStatusErrorReportRequest $request
     * @param Report $errorReport
     *
     * @return [type]
     */
    public function updateStatus(UpdateStatusErrorReportRequest $request, Report $errorReport)
    {
        $this->authorize('view', User::find($errorReport->user_id));
        $user = auth()->user()->id;
        $errorReport = $this->errorReportRepository->updateStatus($request->all(), $errorReport, $user);
        return (new ErrorReportResource($errorReport))
            ->additional(['message' => trans('admin.error_report_status_updated')]);
    }
}
