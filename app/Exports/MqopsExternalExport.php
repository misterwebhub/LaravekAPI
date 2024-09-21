<?php

namespace App\Exports;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\QueryBuilder;
use App\Models\MqopsExternalMeeting;

class MqopsExternalExport implements FromCollection, WithHeadings, ShouldAutoSize, WithMapping
{
    /**
     * @return \Illuminate\Support\Collection
     */

    public function collection()
    {
        $data = QueryBuilder::for(MqopsExternalMeeting::class)
        ->latest()->get();
        return $data;
    }

    public function map($data): array
    {
        $users = array();
        foreach ($data->users as $key => $value)
        {
            $users[$key] =  $value['name']; 
        }
        return [
            $data->user['name'] ?? null,
            isset($users) ? implode(",", $users) : null,
            isset($data->start_date) ? date('Y-m-d', strtotime($data->start_date)) : null,
            isset($data->end_date) ? date('Y-m-d', strtotime($data->end_date)) : null,
            $data->state->name ?? null,
            $data->mqopsPartnerType->name ?? null,
            $data->organisation->name ?? null,
            $data->contact_person ?? null,
            $data->contact_person_designation ?? null,
            $data->contact_people_count ?? null,
            $data->summary ?? null,
            
            
            
        ];

    }

    public function headings(): array
    {
        return [
            trans('admin.mqops_created_user'),
            trans('admin.team_members'),
            trans('admin.mqops_start_date'),
            trans('admin.mqops_end_date'),
            trans('admin.mqops_state'),
            trans('admin.mqops_partner_type_name'),
            trans('admin.mqops_organisation'),
            trans('admin.mqops_contact_person'),
            trans('admin.mqops_contact_person_designation'),
            trans('admin.mqops_contact_people_count'),
            trans('admin.mqops_summary'),
            
            
        ];
    }
     
}