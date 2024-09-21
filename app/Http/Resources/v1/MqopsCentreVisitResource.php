<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class MqopsCentreVisitResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user_name' => $this->user->name,
            'centre_type_id' => $this->centre_type_id,
            'centre_type_name' => $this->centreType->name ?? "",
            'centre_type_type' => $this->centreType->type ?? "",
            'centre' =>  $this->centres,
            'team_members' => $this->users,
            'visit_purpose' => $this->visit_purpose,
            'start_date_actual' => $this->start_date,
            'start_date' => isset($this->start_date) ?
                ($this->start_date->format(config('app.date_format')) ?: null) : null,
            'end_date_actual' => $this->end_date,
            'end_date' => isset($this->end_date) ?
                ($this->end_date->format(config('app.date_format')) ?: null) : null,
            'state' => $this->state_id,
            'state_name' => $this->state->name ?? "",
            'district' => $this->district_id,
            'district_name' => $this->district->name ?? "",
            'feedback' => $this->feedback ?? null,
            'infrastructure_issues' => $this->infrastructure_issues ?? null,
            'good_practice' => $this->good_practice ?? null,
            'rating' => $this->rating,
            'infrastructure' => $this->infrastructure,
            'publicity_material' => $this->publicity_material,
            'quest_content' => $this->quest_content,
            'placement_issue' => $this->placement_issue,
            'immediate_action' => $this->immediate_action,
            'mqops_document' => MqopsDocumentResource::collection($this->mqopsDocuments ?? []),
            'student_data' => $this->student_data ?? null,
            'meet_authority' => $this->meet_authority ?? null,
            'trainer_issue' => $this->trainer_issues ?? null,
            'mobilization_issue' => $this->mobilization_issues ?? null,
            'student_count' => $this->student_count ?? null,
            'attendance_issue' => $this->attendance_issues ?? null,
            'digital_lesson' => $this->digital_lesson ?? null,
        ];
    }
}
