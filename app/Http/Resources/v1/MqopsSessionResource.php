<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Resources\Json\JsonResource;

class MqopsSessionResource extends JsonResource
{
    /* Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        if (!$this->session_type_id) {
            return parent::toArray($request);
        }
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user_name' => $this->user->name,
            'session_type_id' => $this->session_type_id,
            'session_type_name' => $this->sessionType->name,
            'state_id' => $this->state_id,
            'state_name' => $this->state->name,
            'mqops_activity_medium_id' => $this->mqops_activity_medium_id,
            'mqops_activity_medium_name' => $this->sessionMedium->name,
            'bootcamp_type_id' => $this->bootcamp_type_id,
            'other_session_details' => $this->other_session_details,
            'centre_type_id' => $this->centre_type_id,
            'centre_type_name' => $this->centreType->name,
            'centre' =>  $this->centres,
            'project_id' => $this->project_id,
            'phase_id' => $this->phase_id,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'duration' => $this->duration,
            'ext_person_name' => $this->ext_person_name,
            'company_name' => $this->company_name,
            'guest_type_id' => $this->guest_type_id,
            'volunteer_count' => $this->volunteer_count,
            'session_details' => $this->session_details,
            'participant_count' => $this->participant_count,
            'male_participant_count' => $this->male_participant_count,
            'female_participant_count' => $this->female_participant_count,
            'other_participant_count' => $this->other_participant_count,
            'topics_covered' => $this->topics_covered,
            'es_trainer_present' => $this->es_trainer_present,
            'career_club_role' => $this->career_club_role,
            'require_more_support' => $this->require_more_support,
            'support_for' => $this->convertToInt(explode(",", $this->support_for)),
            'mobile_access_count' => $this->mobile_access_count,
            'insight_from_learners' => $this->insight_from_learners,
            'need_support_explore' => $this->need_support_explore,
            'support_for_app' => $this->convertToInt(explode(",", $this->support_for_app)),
            'organised_by_institution' => $this->organised_by_institution,
            'any_practice' => $this->any_practice,
            'key_highlights' => $this->key_highlights,
            'have_resources' => $this->convertToInt(explode(",", $this->have_resources)),
            'others_institution' => $this->others_institution,
            'others_support' => $this->others_support,
            'others_support_app' => $this->others_support_app,
            'leaders_role' => $this->leaders_role,
        ];
    }

    private function convertToInt($val)
    {
        $array = [];
        foreach ($val as $value) {
            if (trim($value) != "" && $value != null) {
                $array[] = (int)$value;
            }
        }
        return $array;
    }
}
