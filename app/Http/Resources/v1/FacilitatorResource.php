<?php

namespace App\Http\Resources\v1;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class FacilitatorResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'mobile' => $this->mobile,
            'gender' => $this->gender,
            'status' => $this->status,
            'organisation_id' => $this->organisation_id,
            'organisation' => $this->organisation->name ?? null,
            'centre_id' => $this->centre_id,
            'centre' => $this->centre->name ?? null,
            'state_id' => $this->centre->state_id ?? null,
            'state' => $this->centre->state->name ?? null,
            'designation' => $this->facilitatorDetail->designation ?? null,
            'qualification' => $this->facilitatorDetail->qualification ?? null,
            'experience' => $this->facilitatorDetail->experience ?? null,
            'is_super_facilitator' => $this->is_super_facilitator,
            'is_master_trainer' => $this->is_master_trainer,
            'date_of_birth' => isset($this->facilitatorDetail->date_of_birth) ? (Carbon::parse($this->facilitatorDetail->date_of_birth)
                ->timezone(config('app.timezone'))->format('Y-m-d H:i:s') ?: null) : null,
            'date_of_birth_readable' => isset($this->facilitatorDetail->date_of_birth) ?
            ($this->facilitatorDetail->date_of_birth->format(config('app.date_format')) ? : null) : null,
            'approval' => $this->is_approved,
            'is_facilitator_approved' => isset($this->facilitatorDetail) ? $this->facilitatorDetail->user_approved : null,
        ];
    }
}
