<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Resources\Json\JsonResource;

class PlacementResource extends JsonResource
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
            'tenant' => $this->tenant_id,
            'placement_type_id' => $this->placement_type_id,
            'placement_type' => $this->placementType->name ?? null,
            'placement_status_id' => $this->placement_status_id,
            'placement_status' => $this->placementStatus->name ?? null,
            'placement_course_id' => $this->placement_course_id,
            'placement_course' => $this->placementCourse->name ?? null,
            'company' => $this->company ?? null,
            'designation' => $this->designation ?? null,
            'centre' => $this->centre_id ?? null,
            'district_id' => $this->location_id ?? null,
            'district' => $this->location->name ?? null,
            'sector_id' => $this->sector_id ?? null,
            'sector' => $this->sector->name ?? null,
            'offerletter_status_id' => $this->offerletter_status_id ?? null,
            'offerletter_status' => $this->offerletterStatus->name ?? null,
            'offerletter_type_id' => $this->offerletter_type_id ?? null,
            'offerletter_type' => $this->offerletterType->name ?? null,
            'salary' => $this->salary ?? null,
            'reason' => $this->reason ?? null,
        ];
    }
}
