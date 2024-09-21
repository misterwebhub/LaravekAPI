<?php
namespace App\Exports;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use App\Models\MqopsTot;
use App\Models\MqopsTotSummaryProject;
use App\Models\Project;
use App\Models\State;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Support\Facades\DB;
class MqopsTotExport implements FromCollection, WithHeadings, WithStrictNullComparison
{
    
    public function collection()
    {
        $data = DB::select(
            "SELECT u.name as user_name,p.name as project_name,s.name as state_name,mts.mode, mts.ecosystem_id,
            mtsp.participant_count, mtsp.female_participant_count,mtsp.male_participant_count,mtsp.other_participant_count,
            mtt.name as tot_name,mts.start_date,mts.end_date
            FROM mqops_tot_summary mts
            inner join mqops_tot_summary_project mtsp
            on mts.id = mtsp.tot_summary_id
            inner join users u on u.id=mts.user_id
            inner join projects p on mtsp.project_id=p.id
            inner join mqops_tot_types mtt on mtt.id = mts.tot_id
            inner join states s on mtsp.state_id=s.id order by mts.created_at desc"
        );
        
        $project_list = array();
        
        foreach ($data as $key => $val)
        {
            $project_list[$key]['user_name'] = $val->user_name;
            $project_list[$key]['mode'] = config('mqops.mode.' . $val->mode);
            $project_list[$key]['ecosystem_id'] = config('mqops.ecosystem.' . $val->ecosystem_id);
            $project_list[$key]['tot_name'] = $val->tot_name;
            $project_list[$key]['start_date'] = $val->start_date;
            $project_list[$key]['end_date'] = $val->end_date;
            $project_list[$key]['project_name'] = $val->project_name;
            $project_list[$key]['state_name'] = $val->state_name;
            $project_list[$key]['participant_count'] = isset($val->participant_count) ? $val->participant_count : null;
            $project_list[$key]['female_participant_count'] = isset($val->female_participant_count) ? $val->female_participant_count : null;
            $project_list[$key]['male_participant_count'] = isset($val->male_participant_count) ? $val->male_participant_count : null;
            $project_list[$key]['other_participant_count'] = isset($val->other_participant_count) ? $val->other_participant_count : null;
        }
        
        return collect($project_list);
    }

    public function headings(): array
    {
        return [
            trans('admin.mqops_created_user'),
            trans('admin.tot_mode'),
            trans('admin.ecosystem_name'),
            trans('admin.tot_name'),
            trans('admin.mqops_start_date'),
            trans('admin.mqops_end_date'),
            trans('admin.centre_project'),
            trans('admin.state'),
            trans('admin.tot_total_participant'),
            trans('admin.tot_female_participant'),
            trans('admin.tot_male_participant'),
            trans('admin.tot_other_participant'),
        ];
    }
}