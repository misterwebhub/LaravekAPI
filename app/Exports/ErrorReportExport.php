<?php

namespace App\Exports;

use App\Models\Report;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Carbon\Carbon;
use App\Models\User;
use App\Models\ErrorReportView;

class ErrorReportExport implements FromQuery, WithHeadings, ShouldAutoSize, WithMapping
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function query()
    {
        if (auth()->user()->hasPermissionTo('organisation.administrator')) {
            $errorReport = QueryBuilder::for(ErrorReportView::class)
                ->join('users', 'users.id', '=', 'error_report_view.user_id')
                ->select('error_report_view.*')
                ->where('users.organisation_id', auth()->user()->organisation_id);
        } elseif (auth()->user()->hasPermissionTo('centre.administrator')) {
            $errorReport = QueryBuilder::for(ErrorReportView::class)
                ->join('users', 'users.id', '=', 'error_report_view.user_id')
                ->select('error_report_view.*')
                ->where('users.centre_id', auth()->user()->centre_id);
        } elseif (auth()->user()->hasPermissionTo('program.administrator')) {
            $errorReport = QueryBuilder::for(ErrorReportView::class)
                ->join('users', 'users.id', '=', 'error_report_view.user_id')
                ->join('organisations', 'users.organisation_id', '=', 'organisations.id')
                ->join('organisation_program', 'organisations.id', '=', 'organisation_program.organisation_id')
                ->select('error_report_view.*')
                ->where('organisation_program.program_id', auth()->user()->program_id);
        } elseif (auth()->user()->hasPermissionTo('project.administrator')) {
            $errorReport = QueryBuilder::for(ErrorReportView::class)
                ->join('users', 'users.id', '=', 'error_report_view.user_id')
                ->join('centres', 'users.centre_id', '=', 'centres.id')
                ->join('centre_project', 'centres.id', '=', 'centre_project.centre_id')
                ->select('users.*')
                ->where('centre_project.project_id', auth()->user()->project_id);
        } else {
            $errorReport = QueryBuilder::for(ErrorReportView::class);
        }
        $errorReport = $errorReport->allowedFilters([
            'category.name', AllowedFilter::exact('category.id'),
            AllowedFilter::exact('subCategory.id'), AllowedFilter::exact('user.centre_id'),
            AllowedFilter::exact('user.state'), AllowedFilter::exact('status')
        ])->where('error_report_view.tenant_id', getTenant())
        ->latest();
        return $errorReport;
    }
    public function map($errorReport): array
    {
        $status = $errorReport->status ?? null;
        if (!is_null($status)) {
            if ($status === REPORT::TYPE_OPEN) {
                $status = trans('admin.open');
            } elseif ($status === REPORT::TYPE_CLOSED) {
                $status = trans('admin.closed');
            } elseif ($status === REPORT::TYPE_REOPENED) {
                $status = trans('admin.reopened');
            } elseif ($status === REPORT::TYPE_PENDING) {
                $status = trans('admin.pending');
            }
        }

        $profile = $errorReport->type ?? null;
        if ($profile === REPORT::TYPE_FACILITATOR) {
            $profile = trans('admin.facilitator');
        } elseif ($profile === REPORT::TYPE_STUDENT) {
            $profile = trans('admin.student');
        } elseif ($profile === REPORT::TYPE_ALUMINI) {
            $profile = trans('admin.alumini');
        } elseif ($profile === REPORT::TYPE_ADMIN) {
            $profile = trans('admin.admin');
        }

        return [
            $errorReport->category_name,
            $errorReport->subcategory_name ?? "",
            $errorReport->lesson_name ?? "",
            $errorReport->subject_name ?? "",
            $errorReport->user_name ?? "",
            $errorReport->email ?? "",
            $errorReport->mobile ?? "",
            $errorReport->centre_name ?? "",
            $errorReport->organisation_name ?? "",
            $errorReport->batch_name ?? "",
            isset($errorReport->created_at) ?
                ($errorReport->created_at->format(config('app.date_format')) ?: "") : "",
            (isset($errorReport->resolution_date) && $errorReport->resolution_date) ?
                Carbon::parse($errorReport->resolution_date)->format('d-m-Y') : "",
            $profile,
            $status,
            $errorReport->issue_severity,
        ];
    }

    public function headings(): array
    {
        return [
            trans('admin.issue'),
            trans('admin.issue_category'),
            trans('admin.lesson'),
            trans('admin.toolkit'),
            trans('admin.contact_name'),
            trans('admin.contact_email'),
            trans('admin.contact_number'),
            trans('admin.center'),
            trans('admin.organisation'),
            trans('admin.batch'),
            trans('admin.date'),
            trans('admin.resolution_date'),
            trans('admin.profile'),
            trans('admin.status'),
            trans('admin.severity'),
        ];
    }
}
