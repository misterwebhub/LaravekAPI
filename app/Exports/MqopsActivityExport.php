<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use App\Models\MqopsActivity;
use Spatie\QueryBuilder\QueryBuilder;

class MqopsActivityExport implements FromCollection, WithHeadings, ShouldAutoSize, WithMapping
{
    /**
     * @return \Illuminate\Support\Collection
     */

    public function collection()
    {
        $data = QueryBuilder::for(MqopsActivity::class)->latest()->get();
        return $data;
    }

    public function map($data): array
    {
        $centres = array();
        foreach ($data->centres as $key => $val) {
            $centres[$key] = $val['name'];
        }

        $career_day_covered = array();
        if(!empty($data->career_day_covered))
        { 
            $arr = explode(",",$data->career_day_covered);
            
            foreach ($arr as $key1 => $val1)
            {
                
                $career_day_covered[$key1] = config('mqops.careerday.' . $val1);
            }
            
        }

        $career_club_place = array();
        if(!empty($data->career_club_place))
        { 
            $arr = explode(",",$data->career_club_place);
            
            foreach ($arr as $key2 => $val2)
            {
                
                $career_club_place[$key2] = config('mqops.careerplace.' . $val2);
            }
            
        }

        
        
        return [
            $data->user['name'] ?? "",
            $data->activityType->name ?? "",
            $data->details ?? "",
            isset($data->mode) ? config('mqops.mode.' . $data->mode): null,
            $data->centreType->name ?? "",
            $data->state->name ?? "",
            isset($centres) ? implode(",", $centres) : "",
            isset($data->start_date) ? date('Y-m-d', strtotime($data->start_date)) : "",
            isset($data->end_date) ? date('Y-m-d', strtotime($data->end_date)) : "",
            $data->duration ?? "",
            $data->company ?? "",
            $data->ext_person_det ?? "",
            $data->participants_count ?? "",
            $data->female_participants_count ?? "",
            $data->male_participants_count ?? "",
            $data->other_participants_count ?? "",
            $data->parents_count ?? "",
            $data->female_parents_count ?? "",
            $data->male_parents_count ?? "",
            $data->other_parents_count ?? "",
            $data->guest_count ?? "",
            $data->description_parentmeet ?? "",
            ((string)$data->is_member_organise != null && trim((string)$data->is_member_organise) != '') ? config('mqops.common.' . $data->is_member_organise) : "",
            ((string)$data->is_member_facilitate != null && trim((string)$data->is_member_facilitate) != '') ? config('mqops.common.' . $data->is_member_facilitate) : "",
            //$data->leader_type_id ?? "",
            !empty($data->leader->name) ? $data->leader->name : "",
            $data->insight_from_parent ?? "",
            $data->company_visited ?? "",
            $data->job_identified ?? "",
            $data->sector_covered ?? "",
            $data->alumni_count ?? "",
            $data->female_alumni_count ?? "",
            $data->male_alumni_count ?? "",
            $data->other_alumni_count ?? "",
            $data->hightlights_alumni ?? "",
            ((string)$data->officer_present_status != null && trim((string)$data->officer_present_status) != '') ? config('mqops.commontwo.' . $data->officer_present_status) : "",
            ((string)$data->careerclub_role != null && trim((string)$data->careerclub_role) != '') ? config('mqops.careerclub.' . $data->careerclub_role) : "",
            $data->emp_count ?? "",
            $data->emp_job_offer ?? "",
            $data->shortlisted_count ?? "",
            $data->male_shortlisted_count ?? "",
            $data->female_shortlisted_count ?? "",
            $data->other_shortlisted_count ?? "",
            $data->selected_count ?? "",
            $data->male_selected_count ?? "",
            $data->female_selected_count ?? "",
            $data->other_selected_count ?? "",
            ((string)$data->officer_present_placement != null && trim((string)$data->officer_present_placement) != '') ? config('mqops.commontwo.' . $data->officer_present_placement) : "",
            $data->offer_with_min_wage ?? "",
            $data->highlight_industryvisit ?? "",
            !empty($data->any_highlight_from_learner) ? $data->any_highlight_from_learner == 1 ? "Yes" : "No" : null,
            $data->highlight_from_learner ?? "",
            $data->other_attendees_count ?? "",
            !empty($data->parent_present) ? $data->parent_present == 1 ? "Yes" : "No" : null,
            $data->parent_count ?? "",
            !empty($career_club_place) ? implode(",",$career_club_place) : null,
            !empty($career_day_covered) ? implode(",",$career_day_covered) : null,
            $data->other_cdc ?? "",
            $data->highlights_plcementcell ?? "",
            ((string)$data->mock_interview_conducted != null && trim((string)$data->mock_interview_conducted) != '') ? config('mqops.mockinterviewconducted.' . $data->mock_interview_conducted) : "",
            $data->mep_name ?? "",
            $data->mep_company ?? "",
            $data->mock_participated_count ?? "",
            !empty($data->any_onboarded) ? $data->any_onboarded == 1 ? "Yes" : "No" : null,
            $data->onboarded_count ?? "",
            !empty($data->any_job_added) ? $data->any_job_added == 1 ? "Yes" : "No" : null,
            $data->job_added_count ?? "",
            isset($data->month_year) ? date('F', strtotime($data->month_year)) . " " . date('Y', strtotime($data->month_year)) : ""
            ];
    }

    public function headings(): array
    {
        return [
            trans('admin.mqops_created_user'),
            trans('admin.mqops_activity_medium'),
            trans('admin.activity_details'),
            trans('admin.mode'),
            trans('admin.mqops_institution_type'),
            trans('admin.mqops_state'),
            trans('admin.centre_session'),
            trans('admin.mqops_start_date'),
            trans('admin.mqops_end_date'),
            trans('admin.session_duration'),
            trans('admin.activity_company'),
            trans('admin.activity_ext_person_det'),
            trans('admin.participant_count_session'),
            trans('admin.participant_count_session_female'),
            trans('admin.participant_count_session_male'),
            trans('admin.participant_count_session_other'),
            trans('admin.activity_parents_count'),
            trans('admin.activity_female_parents_count'),
            trans('admin.activity_male_parents_count'),
            trans('admin.activity_other_parents_count'),
            trans('admin.activity_guest_count'),
            trans('admin.activity_description_parentmeet'),
            trans('admin.activity_is_member_organise'),
            trans('admin.activity_is_member_facilitate'),
            trans('admin.activity_leader_type_id'),
            trans('admin.activity_insight_from_parent'),
            trans('admin.activity_company_visited'),
            trans('admin.activity_job_identified'),
            trans('admin.activity_sector_covered'),
            trans('admin.activity_alumni_count'),
            trans('admin.activity_female_alumni_count'),
            trans('admin.activity_male_alumni_count'),
            trans('admin.activity_other_alumni_count'),
            trans('admin.activity_hightlights_alumni'),
            trans('admin.activity_officer_present_status'),
            trans('admin.activity_careerclub_role'),
            trans('admin.activity_emp_count'),
            trans('admin.activity_emp_job_offer'),
            trans('admin.activity_shortlisted_count'),
            trans('admin.activity_male_shortlisted_count'),
            trans('admin.activity_female_shortlisted_count'),
            trans('admin.activity_other_shortlisted_count'),
            trans('admin.activity_selected_count'),
            trans('admin.activity_male_selected_count'),
            trans('admin.activity_female_selected_count'),
            trans('admin.activity_other_selected_count'),
            trans('admin.activity_officer_present_placement'),
            trans('admin.activity_mimum_wage'),
            trans('admin.activity_highlight_industryvisit'),
            trans('admin.activity_any_highlight_from_learner'),
            trans('admin.activity_highlight_from_learner'),
            trans('admin.activity_other_attendees_count'),
            trans('admin.activity_parent_present'),
            trans('admin.activity_parent_count'),
            trans('admin.activity_career_club_place'),
            trans('admin.activity_career_day_covered'),
            trans('admin.activity_other_cdc'),
            trans('admin.activity_highlights_plcementcell'),
            trans('admin.activity_mock_interview_conducted'),
            trans('admin.ext_person_name'),
            trans('admin.activity_ext_company'),
            trans('admin.activity_cell_member_count'),
            trans('admin.activity_any_onboarded'),
            trans('admin.activity_onboarded_count'),
            trans('admin.activity_any_job_added'),
            trans('admin.activity_job_added_count'),
            trans('admin.activity_month_year')
        ];
    }


    private function convertToInt($val)
    {
        $array = [];
        foreach ($val as $value) {
            $array[] = ($value != "") ? (int)$value : "";
        }
        return empty($array[0]) ? [] : $array;
    }
} 
