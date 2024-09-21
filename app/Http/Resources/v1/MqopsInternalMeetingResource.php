<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Resources\Json\JsonResource;

class MqopsInternalMeetingResource extends JsonResource
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
            'start_date' => $this->start_date ?? null,
            'end_date' => $this->end_date ?? null,
            'state_id' => $this->state_id ?? null,
            'state_name' => $this->state->name ?? null,
            'summary' => $this->summary ?? null,
            'team_members' => $this->teamMembers()->get(['id', 'name'])->toArray(),
            'mqops_document' => MqopsDocumentResource::collection($this->mqopsDocument ?? []),
            'created_by' => $this->created_by ?? null,
            'created_by_name' => $this->user['name'] ?? null,

        ];
    }
}
