<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Resources\Json\JsonResource;

class StudentPersonalInfoResource extends JsonResource
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
            'type' => $this->type,
            'organisation_id' => $this->organisation_id,
            'organisation' => $this->organisation->name ?? null,
            'batch_id' => $this->studentDetail->batch_id,
            'batch' => $this->studentDetail->batch->name ?? trans('admin.batch_not_assigned'),
            'centre_id' => $this->centre_id,
            'centre' => $this->centre->name ?? null,
            'date_of_birth' => $this->studentDetail->date_of_birth,
            'date_of_birth_readable' => $this->studentDetail->date_of_birth
                ->format(config('app.date_format')) ?? null,
            'guardian_name' => $this->studentDetail->guardian_name,
            'guardian_income' => $this->studentDetail->guardian_income,
            'educational_qualification_id' => $this->educational_qualification_id,
            'educational_qualification' => $this->educational_qualification->name ?? null,
            'created_at' => $this->created_at,
        ];
    }
}
