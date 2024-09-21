<?php

namespace App\Exports;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\QueryBuilder;
use App\Models\MqopsCentreVisit;

class MqopsCentreExport implements FromCollection, WithHeadings, ShouldAutoSize, WithMapping
{
    /**
     * @return \Illuminate\Support\Collection
     */

    public function collection()
    {
        $data = QueryBuilder::for(MqopsCentreVisit::class)
            ->latest()
            ->get();
        return $data;
    }

    public function map($data): array
    {
        $centres = array();
        foreach ($data->centres as $key => $val)
        {
            $centres[$key] = $val['name']; 
        }

        $users = array();
        foreach ($data->users as $key1 => $val1)
        {
            $users[$key1] = $val1['name'];
        }
    
        return [
            $data->user->name ?? null,
            $data->centreType->name ?? null,
            $data->state->name ?? null,
            $data->district->name ?? null,
            isset($centres) ? implode(",", $centres) : null,
            isset($users) ? implode(",", $users) : null,
            isset($data->start_date) ? date('Y-m-d', strtotime($data->start_date)) : null,
            isset($data->end_date) ? date('Y-m-d', strtotime($data->end_date)) : null,
            $data->visit_purpose ?? null,
            $data->infrastructure == 1 ? "Yes" : "No",
            $data->infrastructure_issues ?? null,
            $data->good_practice ?? null,
            $data->publicity_material == 1 ? "Yes" : "No",
            $data->placement_issue == 1 ? "Yes" : "No",
            $data->quest_content == 1 ? "Yes" : "No",
            $data->immediate_action == 1 ? "Yes" : "No",
            $data->student_data == 1 ? "Yes" : "No",
            $data->meet_authority == 1 ? "Yes" : "No",
            $data->trainer_issues ? "Yes" : "No",
            $data->mobilization_issues ? "Yes" : "No",
            $data->digital_lesson ? "Yes" : "No",
            $data->attendance_issues ? "Yes" : "No",
            $data->feedback ?? null,
            $data->rating ?? null
            
            
            

        ];
    }

    public function headings(): array
    {
        return [
            trans('admin.mqops_created_user'),
            trans('admin.centre_institute_type'),
            trans('admin.state'),
            trans('admin.district'),
            trans('admin.centre'),
            trans('admin.team_members'),
            trans('admin.mqops_start_date'),
            trans('admin.mqops_end_date'),
            trans('admin.visit_purpose'),
            trans('admin.infrastructure_issues'),
            trans('admin.mqops_infrastructure'),
            trans('admin.good_practice'),
            trans('admin.mqops_publicity_material'),
            trans('admin.mqops_placement_issue'),
            trans('admin.mqops_quest_content'),
            trans('admin.mqops_immediate_action'),
            trans('admin.student_data_centre'),
            trans('admin.meet_authority'),
            trans('admin.trainer_issues'),
            trans('admin.mobilization_issues'),
            trans('admin.digital_lesson'),
            trans('admin.attendance_issues'),
            trans('admin.feedback'),
            trans('admin.mqops_rating')
            
            
            
            
        ];
    }
     
}