<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Resources\Json\JsonResource;

class CentreResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        if (!$this->name) {
            return parent::toArray($request);
        }
        return [
            'id' => $this->id,
            'name' => $this->name ?? "",
            'email' => $this->email,
            'mobile' => $this->mobile,
            'state' => $this->state->name ?? "",
            'state_id' => $this->state_id,
            'district' => $this->district->name ?? "",
            'district_id' => $this->district_id,
            'city' => $this->city,
            'location' => $this->location,
            'pincode' => $this->pincode,
            'website' => $this->website,
            'organisation' => $this->organisation->name ?? "",
            'organisation_id' => $this->organisation_id,
            'activation' => $this->activation_code,
            'type' => $this->centreType->name ?? "",
            'type_id' => $this->centre_type_id,
            'working_mode' => config('staticcontent.workingMode.' . $this->working_mode),
            'working_mode_id' => $this->working_mode,
            'address' => $this->address,
            'registration' => $this->allow_registration,
            'job' => $this->allow_job,
            'resource' => $this->allow_resource,
            'lesson' => $this->lesson_lock,
            'status' => $this->status,
            'active' => $this->active,
            'project' => $this->projects,
            'project_name' => $this->projects->pluck('name'),
            'synced_at' => $this->synced_at ? $this->synced_at->format(config('app.date_format')) : null,
            'partnership_type' => $this->partnershipType->name ?? null,
            'partnership_type_id' => $this->partnership_type_id ?? null,
            'target_students' => $this->target_students,
            'target_trainers' => $this->target_trainers,
            'configure_batch_alumni' => $this->batch_alumni_configure,
            'configure_allow_batch' => $this->allow_batch,
            'batch_end_interval' => $this->batch_end_interval,
            'approval' => $this->is_approved,
            'auto_update' => $this->auto_update_phase,
            'center_id' => $this->center_id,
        ];
    }
}
