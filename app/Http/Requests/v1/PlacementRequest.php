<?php

namespace App\Http\Requests\v1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Placement;

class PlacementRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'placement_type' => 'required|exists:placement_types,id',
            'placement_status' => 'nullable|exists:placement_status,id',
            'course' => 'nullable|exists:placement_courses,id',
            'sector' => 'nullable|exists:sectors,id',
            'offerletter_status' => 'nullable|exists:offerletter_status,id',
            'offerletter_type' => 'nullable|exists:offerletter_types,id',
            'district' => 'nullable|exists:locations,id',
            'company' => 'nullable',
            'salary' => 'nullable',
            'designation' => 'nullable',
            'reason' => 'nullable',

        ];

        return $rules;
    }
}
