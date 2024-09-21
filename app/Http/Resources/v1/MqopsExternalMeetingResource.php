<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class MqopsExternalMeetingResource extends JsonResource
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
            'start_date_actual' => $this->start_date,
            'start_date' => isset($this->start_date) ?
                ($this->start_date->format(config('app.date_format')) ?: null) : null,
            'end_date_actual' => $this->end_date,
            'end_date' => isset($this->end_date) ?
                ($this->end_date->format(config('app.date_format')) ?: null) : null,
            'state' => $this->state_id,
            'state_name' => $this->state->name,
            'summary' => $this->summary ?? null,
            'contact_person' => $this->contact_person ?? null,
            'contact_person_designation' => $this->contact_person_designation ?? null,
            'org_name' => $this->org_name ?? null,
            'organisation_name' => $this->organisation->name ?? null,
            'organisation_id' => $this->organisation_id ?? null,
            'contact_people_count' => $this->contact_people_count ?? null,
            'partner_type' => $this->mqops_partner_type_id ?? null,
            'partner_type_name' => $this->mqopsPartnerType->name,
            'team_members' => $this->users()->get(['id', 'name'])->toArray(),
            'mqops_documents' => MqopsDocumentResource::collection($this->mqopsDocuments ?? []),
            'created_by' => $this->created_by ?? null,
            'created_by_name' => $this->user['name'] ?? null,
        ];
    }
}
