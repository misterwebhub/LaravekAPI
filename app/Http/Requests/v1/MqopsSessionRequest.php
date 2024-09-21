<?php

namespace App\Http\Requests\v1;

use Illuminate\Foundation\Http\FormRequest;

class MqopsSessionRequest extends FormRequest
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
            'user_id' => 'required|exists:users,id',
            'session_type_id' => 'required|exists:session_types,id',
            'state_id' => 'exists:states,id',
            'mqops_activity_medium_id' => 'exists:mqops_activity_mediums,id',
            'bootcamp_type_id' => 'numeric|in:0,1,2,3,4,5',
            'other_session_details' => '',
            'centre_type_id' => 'exists:centre_types,id',
            'centre' => 'array|exists:centres,id',
            'project_id' => 'exists:projects,id',
            'phase_id' => 'exists:phases,id',
            'start_date' => 'date',
            'end_date' => 'date',
            'duration' => 'numeric',
            'ext_person_name' => '',
            'company_name' => '',
            'guest_type_id' => 'numeric|in:0,1,2,3,4,5',
            'volunteer_count' => 'numeric',
            'session_details' => '',
            'participant_count' => 'numeric',
            'male_participant_count' => 'numeric',
            'female_participant_count' => 'numeric',
            'other_participant_count' => 'numeric',
            'topics_covered' => '',
            'es_trainer_present' => 'numeric|in:0,1,2,3,4,5',
            'career_club_role' => 'numeric|in:0,1,2,3,4,5',
            'require_more_support' => 'numeric|in:0,1,2,3,4,5',
            'support_for' => '',
            'mobile_access_count' => 'numeric|in:0,1,2,3,4,5',
            'insight_from_learners' => 'numeric|in:0,1,2,3,4,5',
            'need_support_explore' => 'numeric|in:0,1,2,3,4,5',
            'support_for_app' => '',
            'organised_by_institution' => 'numeric|in:0,1,2,3,4,5',
            'any_practice' => '',
            'key_highlights' => '',
            'have_resources' => '',
            'others_institution' => '',
            'others_support' => '',
            'others_support_app' => '',
            'leaders_role' => 'numeric|in:0,1,2,3,4,5',
        ];
        return $rules;
    }
}
