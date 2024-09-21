<?php

namespace App\Http\Requests\v1;

use Illuminate\Foundation\Http\FormRequest;

class MqopsTotRequest extends FormRequest
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
            'mode' => 'numeric|in:0,1,2',
            'venue_tot' => '',
            'ecosystem_id' => 'numeric|in:0,1,2,3,4,5,6',
            'other_ecosystem' => '',
            'tot_id' => 'required|exists:mqops_tot_types,id',
            'other_tot' => '',
            'start_date' => 'date',
            'end_date' => 'date',
            'details.*.project_id' => 'exists:projects,id',
            'details.*.state_id' => 'exists:states,id',
            'details.*.male_participant_count' => 'numeric',
            'details.*.female_participant_count' => 'numeric',
            'details.*.other_participant_count' => 'numeric',
            'details.*.participant_count' => 'numeric',

        ];
        return $rules;
    }
}
