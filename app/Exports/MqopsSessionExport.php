<?php

namespace App\Exports;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use App\Models\MqopsSession;
use Spatie\QueryBuilder\QueryBuilder;

class MqopsSessionExport implements FromCollection, WithHeadings, ShouldAutoSize, WithMapping
{
    /**
     * @return \Illuminate\Support\Collection
     */

    public function collection()
    {
        $data = QueryBuilder::for(MqopsSession::class)->latest('created_at')->get();
        return $data;
    }

    public function map($data): array
    {
        $centres = array();
        if(isset($data->centres))
        {
        foreach ($data->centres as $key => $val)
        {
            $centres[$key] = $val['name']; 
        }
        }
        $support_for = array();
        if(!empty($data->support_for))
        {
            $arr = explode(",",$data->support_for);
            foreach ($arr as $key2 => $val2)
            {
                $support_for[$key2] = config('mqops.support_session.' . $val2);
            }
        }

        $support_for_app = array();
        if(!empty($data->support_for_app))
        {
            $arr1 = explode(",",$data->support_for_app);
            foreach ($arr1 as $key3 => $val3)
            {
                $support_for_app[$key3] = config('mqops.support_session.' . $val3);
            }
        }

        $have_resources = array();
        if(!empty($data->have_resources))
        {
            $arr2 = explode(",",$data->have_resources);
            foreach ($arr2 as $key4 => $val4)
            {
                $have_resources[$key4] = config('mqops.es_curriculam.' . $val4);
            }
        }

        

        return [
            $data->user['name'] ?? null,
            $data->sessionType->name ?? null,
            $data->state->name ?? null,
            $data->sessionMedium->name ?? null,
            ((string)$data->bootcamp_type_id != null && trim((string)$data->bootcamp_type_id) != '') ? config('mqops.bootcamp.' . $data->bootcamp_type_id) : "",
            $data->other_session_details ?? null,
            $data->centreType->name ?? null,
            $data->others_institution ?? null,
            isset($centres) ? implode(",", $centres) : null,
            $data->project->name ?? null,
            $data->phase->name ?? null,
            isset($data->start_date) ? date('Y-m-d', strtotime($data->start_date)) : null,
            isset($data->end_date) ? date('Y-m-d', strtotime($data->end_date)) : null,
            $data->duration ?? null,
            $data->ext_person_name ?? null,
            $data->company_name ?? null,
            ((string)$data->guest_type_id != null && trim((string)$data->guest_type_id) != '') ? config('mqops.guest_type.' . $data->guest_type_id) : "",
            $data->volunteer_count ?? null,
            $data->session_details ?? null,
            $data->participant_count ?? null,
            $data->female_participant_count ?? null,
            $data->male_participant_count ?? null,
            $data->other_participant_count ?? null,
            $data->topics_covered ?? null,
            ((string)$data->es_trainer_present != null && trim((string)$data->es_trainer_present) != '') ? config('mqops.es_trainer_present.' . $data->es_trainer_present) : "",
            ((string)$data->career_club_role != null && trim((string)$data->career_club_role) != '') ? config('mqops.careerclub_session.' . $data->career_club_role) : "",
            isset($data->require_more_support) ? $data->require_more_support == 1 ? "Yes" : "No" : null,
            !empty($support_for) ? implode(",",$support_for) : null,
            ((string)$data->mobile_access_count != null && trim((string)$data->mobile_access_count) != '') ? config('mqops.mobile_access_count.' . $data->mobile_access_count) : "",
            isset($data->insight_from_learners) ? $data->insight_from_learners == 1 ? "Yes" : "No" : null,
            isset($data->need_support_explore) ? $data->need_support_explore == 1 ? "Yes" : "No" : null,
            !empty($support_for_app) ? implode(",",$support_for_app) : null,
            !empty($data->others_support_app) ? $data->others_support_app : null,
            isset($data->organised_by_institution) ? $data->organised_by_institution == 1 ? "Yes" : "No" : null,
            ((string)$data->leaders_role != null && trim((string)$data->leaders_role) != '') ? config('mqops.placementofficer.' . $data->leaders_role) : "",
            $data->any_practice ?? null,
            $data->key_highlights ?? null,
            !empty($have_resources) ? implode(",",$have_resources) : null
            
        ];

    }

    public function headings(): array
    {
        return [
            trans('admin.mqops_created_user'),
            trans('admin.session_type_name'),
            trans('admin.mqops_state'),
            trans('admin.session_medium'),
            trans('admin.session_bootcamp_related'),
            trans('admin.session_details_other'),
            trans('admin.mqops_institution_type'),
            trans('admin.mqops_institution_type_other'),
            trans('admin.centre_session'),
            trans('admin.centre_project'),
            trans('admin.phase'),
            trans('admin.mqops_start_date'),
            trans('admin.mqops_end_date'),
            trans('admin.session_duration'),
            trans('admin.ext_person_name'),
            trans('admin.company_name'),
            trans('admin.session_guest_lecture_type'),
            trans('admin.session_volunteer_count'),
            trans('admin.session_details'),
            trans('admin.participant_count_session'),
            trans('admin.participant_count_session_female'),
            trans('admin.participant_count_session_male'),
            trans('admin.participant_count_session_other'),
            trans('admin.topics_covered'),
            trans('admin.es_trainer_present'),
            trans('admin.career_club_role'),
            trans('admin.require_more_support'),
            trans('admin.support_for'),
            trans('admin.mobile_access_count'),
            trans('admin.insight_from_learners'),
            trans('admin.need_support_explore'),
            trans('admin.support_for_app'),
            trans('admin.other_ecosystem'),
            trans('admin.organised_by_institution'),
            trans('admin.leaders_role'),
            trans('admin.any_practice'),
            trans('admin.key_highlights'),
            trans('admin.have_resources')
            
            ];

    }
     
}