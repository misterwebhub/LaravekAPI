<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Resources\Json\JsonResource;

class MqopsTotDetailResource extends JsonResource
{
    /* Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        if (!$this->tot_summary_id) {
            return parent::toArray($request);
        }
        return [
            'id' => $this->id,
            'tot_summary_id' => $this->tot_summary_id,
            'project_id' => $this->project_id,
            'project_name' => $this->project_id ? $this->project->name : null,
            'state_id' => $this->state_id,
            'state_name' => $this->state_id ? $this->state->name : null,
            'male_participant_count' => $this->male_participant_count,
            'female_participant_count' => $this->female_participant_count,
            'other_participant_count' => $this->other_participant_count,
            'participant_count' => $this->participant_count,
        ];
    }
}
