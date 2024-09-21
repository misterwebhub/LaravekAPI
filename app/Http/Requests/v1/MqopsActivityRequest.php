<?php

namespace App\Http\Requests\v1;

use Illuminate\Foundation\Http\FormRequest;

class MqopsActivityRequest extends FormRequest
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
            'centre_type_id' => 'required|exists:centre_types,id',
            'state_id' => 'required|exists:states,id',
            'mqops_activity_medium_id' => 'required|exists:mqops_activity_mediums,id',
            'centre' => 'required|array|exists:centres,id',
            'batch' => 'array|exists:batches,id',
            'mqops_activity_type_id' => 'required|exists:mqops_activity_types,id',
            'session_start_date' => 'required|date',
            'session_end_date' => 'required|date|after_or_equal:session_start_date',
            'duration' => 'numeric|min:0',
            'participants_count' => 'numeric|min:0|max:1000000000',
            'female_participants_count' => 'numeric|min:0|max:1000000000',
            'male_participants_count' => 'numeric|min:0|max:1000000000',
            'other_participants_count' => 'numeric|min:0|max:1000000000',
            'parents_count' => 'numeric|min:0|max:1000000000',
            'female_parents_count' => 'numeric|min:0|max:1000000000',
            'male_parents_count' => 'numeric|min:0|max:1000000000',
            'other_parents_count' => 'numeric|min:0|max:1000000000',
            'document_link' => 'array',
            'support_of_any_quest' => 'numeric|in:0,1'
        ];
        return $rules;
    }
}
