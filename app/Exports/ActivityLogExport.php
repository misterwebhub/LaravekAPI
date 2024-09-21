<?php

namespace App\Exports;

use App\Models\ActivityLog;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ActivityLogExport implements FromCollection, WithHeadings, ShouldAutoSize, WithMapping
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $logs = QueryBuilder::for(ActivityLog::class)
            ->allowedFilters([
                'description', 'user.name',
                AllowedFilter::scope('user.roles.name')->ignore(null),
            ])
            ->with('user')
            ->latest()
            ->get();

        return $logs;
    }

    public function map($activityLog): array
    {
        return [
            $activityLog->id,
            $activityLog->causer_id,
            $activityLog->user->name ?? null,
            $activityLog->user->roles()->first()->name ?? null,
            $activityLog->description,
            $activityLog->log_name,
            $activityLog->subject_id,
            $activityLog->created_at->format('Y-m-d H:i:s'),
            $this->getData($activityLog->properties),
        ];
    }


    public function headings(): array
    {
        return [
            trans('admin.id'),
            trans('admin.user_id'),
            trans('admin.name'),
            trans('admin.role'),
            trans('admin.description'),
            trans('admin.log_name'),
            trans('admin.table_id'),
            trans('admin.created_date'),
            trans('admin.properties'),

        ];
    }
    private function getData($properties)
    {
        $arrayData = [];
        $data = json_decode($properties, true);
        // Loop through the object
        foreach ($data as $key => $value) {
            if ($key == 'old') {
                $arrayData[] = 'old: ';
            }
            foreach ($value as $key1 => $value1) {
                if ($key1 != 'password') {
                    $arrayData[] = $key1 . ":" . $value1;
                }
            }
        }
        $a = implode(',', $arrayData);
        $y = str_replace("old: ,", "old: ", $a);
        return $y;
    }
}
