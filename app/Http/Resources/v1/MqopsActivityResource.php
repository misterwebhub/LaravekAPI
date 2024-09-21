<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Resources\Json\JsonResource;

class MqopsActivityResource extends JsonResource
{
    /* Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        if (!$this->mqops_activity_type_id) {
            return parent::toArray($request);
        }
        return [
            'id' => $this->id,
            'user_name' => $this->user->name ?? "",
            'centre_type_id' => $this->centre_type_id,
            'centre_type_name' => $this->centreType->name ?? "",
            'state_id' =>  $this->state_id,
            'state_name' =>  $this->state->name ?? "",
            'mqops_activity_medium_id' =>  $this->mqops_activity_medium_id,
            'mqops_activity_medium_name' =>  $this->activityMedium->name ?? "",
            'centre' =>  $this->centres,
            'batch' =>  $this->batches,
            'mqops_activity_type_id' =>  $this->mqops_activity_type_id,
            'mqops_activity_type_name' =>  $this->activityType->name ?? "",
            'session_name' =>  $this->session_name ?? "",
            'session_start_date' =>  $this->session_start_date,
            'session_end_date' =>  $this->session_end_date,
            'duration' =>  $this->duration,
            'participants_count' =>  $this->participants_count,
            'female_participants_count' =>  $this->female_participants_count,
            'male_participants_count' =>  $this->male_participants_count,
            'other_participants_count' =>  $this->other_participants_count,
            'parents_count' =>  $this->parents_count,
            'female_parents_count' =>  $this->female_parents_count,
            'male_parents_count' =>  $this->male_parents_count,
            'other_parents_count' =>  $this->other_parents_count,
            'feedback' =>  $this->feedback,
            'company' =>  $this->company,
            'ext_person_det' => $this->ext_person_det,
            'comapny_person_name' =>  $this->comapny_person_name ?? "",
            'company_person_designation' =>  $this->company_person_designation,
            'support_of_any_quest' => $this->support_of_any_quest,
            'mqops_document' => MqopsDocumentResource::collection($this->documents ?? []),
            'which_team_supported' => $this->which_team_supported,
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
