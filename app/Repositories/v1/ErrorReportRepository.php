<?php

namespace App\Repositories\v1;

use Spatie\QueryBuilder\QueryBuilder;
use App\Models\Report;
use App\Models\User;
use App\Exports\ErrorReportExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Spatie\QueryBuilder\AllowedFilter;
use App\Jobs\ErrorResolved;
use App\Models\Organisation;
use Spatie\QueryBuilder\AllowedSort;
use App\Services\ErrorCategoryCustomSort;
use App\Services\ErrorSubCategoryCustomSort;
use App\Services\ErrorLessonCustomSort;
use App\Services\ErrorSubjectCustomSort;
use App\Services\ErrorCentreCustomSort;
use App\Services\ErrorProfileCustomSort;
use App\Services\Filter\ErrorReportCustomFilter;
use Carbon\Carbon;
use App\Models\ErrorReportView;

/**
 * Class ErrorReportRepository
 * @package App\Repositories
 */
class ErrorReportRepository
{
    /**
     * List all error reports
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function index($request, $user)
    {
        if ($user->hasPermissionTo('organisation.administrator')) {
            $errorReport = QueryBuilder::for(ErrorReportView::class)
                ->join('users', 'users.id', '=', 'error_report_view.user_id')
                ->select('error_report_view.*')
                ->where('users.organisation_id', $user->organisation_id);
        } elseif ($user->hasPermissionTo('centre.administrator')) {
            $errorReport = QueryBuilder::for(ErrorReportView::class)
                ->join('users', 'users.id', '=', 'error_report_view.user_id')
                ->select('error_report_view.*')
                ->where('users.centre_id', $user->centre_id);
        } elseif ($user->hasPermissionTo('program.administrator')) {
            $errorReport = QueryBuilder::for(ErrorReportView::class)
                ->join('users', 'users.id', '=', 'error_report_view.user_id')
                ->join('organisations', 'users.organisation_id', '=', 'organisations.id')
                ->join('organisation_program', 'organisations.id', '=', 'organisation_program.organisation_id')
                ->select('error_report_view.*')
                ->where('organisation_program.program_id', $user->program_id);
        } elseif ($user->hasPermissionTo('project.administrator')) {
            $errorReport = QueryBuilder::for(ErrorReportView::class)
                ->join('users', 'users.id', '=', 'error_report_view.user_id')
                ->join('centres', 'users.centre_id', '=', 'centres.id')
                ->join('centre_project', 'centres.id', '=', 'centre_project.centre_id')
                ->select('error_report_view.*')

                ->where('centre_project.project_id', $user->project_id);
        } else {
            $errorReport = QueryBuilder::for(ErrorReportView::class);
        }
        $totalCount = $errorReport->get()->count();
        $errorReport = $errorReport
            ->allowedFilters([
                'category.name', AllowedFilter::exact('faq_category_id'),
                AllowedFilter::exact('faq_sub_category_id'), AllowedFilter::exact('user_centre_id'),
                AllowedFilter::exact('centre_state_id'), AllowedFilter::exact('status'),
                AllowedFilter::exact('user_type'),
                AllowedFilter::custom('search_value', new ErrorReportCustomFilter()),
            ])
            ->allowedSorts(
                [
                    'created_at', 'status', 'user_name',
                    AllowedSort::custom('category.name', new ErrorCategoryCustomSort()),
                    AllowedSort::custom('subCategory.name', new ErrorSubCategoryCustomSort()),
                    AllowedSort::custom('lesson.name', new ErrorLessonCustomSort()),
                    AllowedSort::custom('subject.name', new ErrorSubjectCustomSort()),
                    AllowedSort::custom('centre.name', new ErrorCentreCustomSort()),
                    AllowedSort::custom('profile', new ErrorProfileCustomSort()),
                ]
            )
            ->latest()
            ->paginate($request['limit'] ?? null);
        return array(
            'errorReport' => $errorReport,
            'count' => $totalCount
        );
    }

    /**
     * @param mixed $request
     * @param mixed $error report
     *
     * @return [type]
     */
    public function updateStatus($request, $errorReport, $user)
    {
        $errorReport->resolved_by = $user;
        if ($request['status'] == Report::TYPE_CLOSED) {
            $errorReport->resolution_date = Carbon::today()->format('Y-m-d');
        }
        $errorReport->status = $request['status'];
        $errorReport->update();
        ErrorResolved::dispatch($errorReport)->onQueue('notification');
        return $errorReport;
    }

    /**
     * Export reports
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function exportErrorReport($request)
    {
        $fileName = "errorReport_downlods/" . time() . "errorReports.csv";
        Excel::store(new ErrorReportExport(), $fileName, 's3');
        return generateTempUrl($fileName);
    }
}
