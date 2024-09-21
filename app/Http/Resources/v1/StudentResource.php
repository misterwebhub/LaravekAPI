<?php

namespace App\Http\Resources\v1;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\User;

class StudentResource extends JsonResource
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
        if (!empty($this->studentDetail)) {
            $experience = array_search(
                trim($this->studentDetail->experience),
                config('staticcontent.student_work_experience')
            );
        } else {
            $experience = null;
        }
        
        if ($experience && $experience !== false) {
            $experience = $this->studentDetail->experience;
            if ($experience == "0") {
                $experience = 0;
            }
        } else {
            $experience = null;
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'mobile' => $this->mobile,
            'gender' => $this->gender,
            'status' => $this->status,
            'reg_status' => ($this->reg_status == User::ACTIVE_STATUS) ? 'Active' : 'Inactive',
            'type' => $this->type,
            'organisation_id' => $this->organisation_id,
            'organisation' => $this->organisation->name ?? null,
            'batch_id' => $this->studentDetail->batch_id ?? null,
            'batch' => $this->studentDetail->batch->name ?? trans('admin.batch_not_assigned'),
            'centre_id' => $this->centre_id,
            'centre' => $this->centre->name ?? null,
            'date_of_birth' => isset($this->studentDetail->date_of_birth) ?
                (Carbon::parse($this->studentDetail->date_of_birth)
                    ->timezone(config('app.timezone'))->format('Y-m-d H:i:s') ?: null) : null,
            'date_of_birth_readable' => isset($this->studentDetail->date_of_birth) ?
                ($this->studentDetail->date_of_birth->format(config('app.date_format')) ?: null) : null,
            'educational_qualification_id' => $this->studentDetail->educational_qualification_id ?? null,
            'educational_qualification' => $this->studentDetail->educationalQualification->name ?? null,
            'marital_status' => isset($this->studentDetail->marital_status) ?
                (config('staticcontent.maritalStatus.' . $this->studentDetail->marital_status) ?: null) : null,
            'guardian_name' => $this->studentDetail->guardian_name ?? null,
            'guardian_email' => $this->studentDetail->guardian_email ?? null,
            'guardian_mobile' => $this->studentDetail->guardian_mobile ?? null,
            'guardian_income' => $this->studentDetail->guardian_income ?? null,
            'guardian_occupation' => $this->studentDetail->guardian_occupation ?? null,
            'placement_status_id' => $this->studentDetail->placement_status_id ?? null,
            'placement_status' => $this->studentDetail->placementStatus->name ?? null,
            'work_experience' => $experience,
            'last_monthly_salary' => $this->studentDetail->last_month_salary ?? null,
            'updated_email' => $this->studentDetail->updated_email ?? null,
            'updated_mobile' => $this->studentDetail->updated_mobile ?? null,
            'trade_id' => $this->studentDetail->trade_id ?? null,
            'trade' => $this->studentDetail->trade->name ?? null,
            'created_at' => $this->created_at,
            'registration_status' => $this->registration_status,
            'is_logged_in' => $this->is_logged_in,
            'is_reg_mail_send' => $this->is_reg_mail_send,
            'approval' => $this->is_approved,

        ];
    }
}
