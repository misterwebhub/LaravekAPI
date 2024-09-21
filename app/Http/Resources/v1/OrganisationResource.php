<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Resources\Json\JsonResource;

class OrganisationResource extends JsonResource
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
            'status' => $this->status,
            'state' => ($this->state->name) ?? null,
            'state_id' => $this->state_id,
            'district' => ($this->district->name) ?? null,
            'district_id' => $this->district_id,
            'city' => $this->city,
            'website' => $this->website,
            'pincode' => $this->pincode,
            'address' => $this->address,
            'programs' =>  implode(',', array_filter($this->program->pluck('name')->toArray())) ?? null,
            'approval' => $this->is_approved,
        ];
    }
}
