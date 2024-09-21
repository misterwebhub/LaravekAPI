<?php

namespace App\Http\Requests\v1;

use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class MqopsCentreVisitRequest extends FormRequest
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
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'state' => 'required|exists:states,id',
            'district' => 'required|exists:districts,id',
            'centre_type_id' => 'required|exists:centre_types,id',
            'centres' => 'required|array|exists:centres,id',
            'team_members' => 'nullable|array|exists:users,id',
            'visit_purpose' => 'required',
            'infrastructure' => 'required|in:0,1',
            'infrastructure_issues' => 'nullable',
            'good_practice' => 'nullable',
            'publicity_material' => 'required|in:0,1',
            'quest_content' => 'required|in:0,1',
            'placement_issue' => 'required|in:0,1',
            'immediate_action' => 'required|in:0,1',
            'feedback' => 'nullable',
            'rating' => 'required|in:1,2,3,4,5',
            'files' => 'array|nullable',
            'student_data' => 'nullable|in:0,1',
            'meet_authority' => 'nullable|in:0,1',
            'trainer_issues' => 'nullable|in:0,1',
            'mobilization_issues' => 'nullable|in:0,1',
            'student_count' => 'nullable',
            'attendance_issues' => 'nullable|in:0,1',
            'digital_lesson' => 'nullable|in:0,1',
        ];
        return $rules;
    }
}
