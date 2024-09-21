<?php

namespace App\Repositories\v1;

use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use App\Models\ActivityLog;
use App\Models\ActivityLogView;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use App\Exports\ActivityLogExport;
use Spatie\QueryBuilder\AllowedSort;
use App\Services\ActivityLogUserCustomSort;
use App\Services\Filter\ActivityLogUserCustomFilter;

/**
 * [Description LogRepository]
 */
class ActivityLogRepository
{
    /**
     * List all logs
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function index($request)
    {
        $logs = QueryBuilder::for(ActivityLogView::class);
        $totalCount = $logs->get()->count();

        $logs = $logs->allowedFilters([
            'description', AllowedFilter::partial('user.name', 'user_name'),
            AllowedFilter::exact('user.roles.name', 'role_name')->ignore(null),
        ])
            ->allowedSorts(
                [
                    'description', 'created_at',
                    AllowedSort::field('user.name', 'user_name'),
                ]
            )
            ->paginate($request['limit'] ?? null);
        return ['logs' => $logs, 'total_count' => $totalCount];
    }

    /**
     * Export activity Log
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function exportActivityLog($request)
    {

        $fileName = "activityLog_downlods/" . time() . "activityLogs.csv";
        Excel::store(new ActivityLogExport(), $fileName, 's3');
        return getImageTemporaryLink($fileName);
    }
}
