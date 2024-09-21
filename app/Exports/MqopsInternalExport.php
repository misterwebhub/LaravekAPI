<?php

namespace App\Exports;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Spatie\QueryBuilder\QueryBuilder;
use App\Models\MqopsInternalMeeting;

class MqopsInternalExport implements FromCollection, WithHeadings, ShouldAutoSize, WithMapping
{
    /**
     * @return \Illuminate\Support\Collection
     */

     public function collection()
    {
        $data = QueryBuilder::for(MqopsInternalMeeting::class)->latest()->get();
        return $data;
    }

    public function map($data): array
    {
        $team_member = array();
        foreach ($data->teamMembers as $key => $value)
        {
            $team_member[$key] =  $value['name']; 
        }
        return [
            $data->user['name'] ?? null,
            isset($team_member) ? implode(",", $team_member) : null,
            $data->state->name ?? null,
            isset($data->start_date) ? date('Y-m-d', strtotime($data->start_date)) : null,
            isset($data->end_date) ? date('Y-m-d', strtotime($data->end_date)) : null,
            $data->summary ?? null,
            
        ];
    }

    public function headings(): array
    {
        return [
            trans('admin.mqops_created_user'),
            trans('admin.team_members'),
            trans('admin.mqops_state'),
            trans('admin.mqops_start_date'),
            trans('admin.mqops_end_date'),
            trans('admin.mqops_summary'),

        ];
    }
     
}