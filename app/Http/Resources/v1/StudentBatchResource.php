<?php

namespace App\Http\Resources\v1;

use App\Models\PlacementType;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentBatchResource extends JsonResource
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
        $onBatchCompletion = $this->placements
            ->where("user_id", $this->id)
            ->where("placement_type_id", PlacementType::where("type", PlacementType::TYPE1)
                ->first()->id)
            ->first();
        $after3Months = $this->placements
            ->where("user_id", $this->id)
            ->where("placement_type_id", PlacementType::where("type", PlacementType::TYPE2)
                ->first()->id)
            ->first();
        $after6Months = $this->placements
            ->where("user_id", $this->id)
            ->where("placement_type_id", PlacementType::where("type", PlacementType::TYPE3)
                ->first()->id)
            ->first();
        $experience = array_search(
            trim($this->studentDetail->experience),
            config('staticcontent.student_work_experience')
        );
        if ($experience !== false) {
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
            'gender' => $this->gender,
            'date_of_birth' => $this->studentDetail->date_of_birth ?? null,
            'date_of_birth_readable' => isset($this->studentDetail->date_of_birth) ?
                ($this->studentDetail->date_of_birth->format('d-m-Y') ?: null) : null,
            'age' => $this->studentDetail->date_of_birth->age ?? null,
            'email' => $this->email,
            'mobile' => $this->mobile,
            'trade_id' => $this->studentDetail->trade_id,
            'trade' => $this->studentDetail->trade->name ?? null,
            'educational_qualification_id' => $this->studentDetail->educational_qualification_id,
            'educational_qualification' => $this->studentDetail->educationalQualification->name ?? null,
            'marital_status' => config('staticcontent.maritalStatus.' . $this->studentDetail->marital_status) ?? null,
            'work_experience' => $experience,
            'last_monthly_salary' => $this->studentDetail->last_month_salary,
            'guardian_name' => $this->studentDetail->guardian_name,
            'guardian_mobile' => $this->studentDetail->guardian_mobile,
            'guardian_income' => $this->studentDetail->guardian_income,
            'guardian_occupation' => $this->studentDetail->guardian_occupation,
            'updated_email' => $this->studentDetail->updated_email,
            'updated_mobile' => $this->studentDetail->updated_mobile,
            'contactability' => (int)$this->studentDetail->contactability,
            'not_contactable_reason' => $this->studentDetail->not_contactable_reason,
            'interview1_company_name' => $this->studentDetail->interview1_company_name,
            'interview1_date' => $this->studentDetail->interview1_date,
            'interview1_result' => (int)$this->studentDetail->interview1_result,
            'interview1_result_status' => config('staticcontent.interview_result.' . $this->studentDetail->interview1_result) ?? null,
            'interview2_company_name' => $this->studentDetail->interview2_company_name,
            'interview2_date' => $this->studentDetail->interview2_date,
            'interview2_result' => (int)$this->studentDetail->interview2_result,
            'interview2_result_status' => config('staticcontent.interview_result.' . $this->studentDetail->interview2_result) ?? null,
            'interview3_company_name' => $this->studentDetail->interview3_company_name,
            'interview3_date' => $this->studentDetail->interview3_date,
            'interview3_result' => (int)$this->studentDetail->interview3_result,
            'interview3_result_status' => config('staticcontent.interview_result.' . $this->studentDetail->interview3_result) ?? null,
            'placed' => (int)$this->studentDetail->placed,
            'placed_status' => config('staticcontent.placed.' . $this->studentDetail->placed) ?? null,
            'month_of_joining' => (int)$this->studentDetail->month_of_joining,
            'month_name' => config('staticcontent.month_of_joining.' . $this->studentDetail->month_of_joining) ?? null,
            'date_of_updation' => $this->studentDetail->date_of_updation,
            'remarks' => $this->studentDetail->remarks,
            'employment_details' =>
            [
                'employment_status_completion' => empty($onBatchCompletion) ?
                    ['placement_type_id' => PlacementType::where("type", PlacementType::TYPE1)
                        ->first()->id] : new PlacementResource($onBatchCompletion),
                'employment_status_3months' => empty($after3Months) ?
                    ['placement_type_id' => PlacementType::where("type", PlacementType::TYPE2)
                        ->first()->id] : new PlacementResource($after3Months),
                'employment_status_6months' => empty($after6Months) ?
                    ['placement_type_id' => PlacementType::where("type", PlacementType::TYPE3)
                        ->first()->id] : new PlacementResource($after6Months),
            ],
        ];
    }
}
