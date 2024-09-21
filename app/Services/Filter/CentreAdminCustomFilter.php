<?php

namespace App\Services\Filter;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;
use App\Models\PlacementType;

class CentreAdminCustomFilter implements Filter
{
    public function __invoke(Builder $query, $value, string $property)
    {
        $batchCompletion = PlacementType::where("type", PlacementType::TYPE1)->first();
        $after3Months = PlacementType::where("type", PlacementType::TYPE2)->first();
        $after6Months = PlacementType::where("type", PlacementType::TYPE3)->first();

        // To avoid search by dob as null
        $dob = strtotime($value);
        $dob = $dob ? date("Y-m-d", $dob) : $value;
        // To avoid search by marital status as null
        $maritalValue = config('staticcontent.maritalStatusValue.' .ucwords($value));
        $maritalValue = isset($maritalValue) ? $maritalValue : $value;
        // To avoid search by experience as null
        $experience = config('staticcontent.student_work_experience.' . trim($value));
        $experience = isset($experience) ? $experience : $value;

        return $query->where(function ($p) use ($value, $maritalValue, $dob, $experience, $after3Months, $batchCompletion, $after6Months) {
            $p->where('name', 'LIKE', '%' . $value . '%')
                ->orWhere('email', 'LIKE', '%' . $value . '%')
                ->orWhere('mobile', 'LIKE', '%' . $value . '%')
                ->orWhere('gender', 'LIKE', $value . '%')

                ->orWhereHas('studentDetail', function ($q) use ($value, $maritalValue, $dob, $experience) {
                    $q->where('guardian_name', 'LIKE', '%' . $value . '%')
                        ->orWhere('last_month_salary', 'LIKE', '%' . $value . '%')
                        ->orWhere('guardian_income', 'LIKE', '%' . $value . '%')
                        ->orWhere('guardian_email', 'LIKE', '%' . $value . '%')
                        ->orWhere('guardian_mobile', 'LIKE', '%' . $value . '%')
                        ->orWhere('marital_status', 'LIKE', '%' . $maritalValue . '%')
                        ->orWhere('gender', 'LIKE', $value . '%')
                        ->orWhere('experience', 'LIKE', '%' . $experience . '%')
                        ->orWhere('date_of_birth', $dob);
                    $q->orWhereHas('trade', function ($p) use ($value) {
                        $p->where('name', 'LIKE', '%' . $value . '%');
                    });
                    $q->orWhereHas('educationalQualification', function ($p) use ($value) {
                        $p->where('name', 'LIKE', '%' . $value . '%');
                    });
                })
                ->orWhereHas('placements', function ($q) use ($value, $batchCompletion, $after3Months, $after6Months) {
                        $q->whereHas('placementStatus', function ($p) use ($value) {
                            $p->where('name', 'LIKE', '%' . $value . '%');
                        })
                    ->where(function ($s) use ($value, $batchCompletion, $after3Months, $after6Months) {
                        $s->where("placement_type_id", $batchCompletion->id)
                            ->orWhere("placement_type_id", $after3Months->id)
                            ->orWhere("placement_type_id", $after6Months->id);
                    });
                });
        });
    }
}
