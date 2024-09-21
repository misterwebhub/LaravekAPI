<?php

namespace App\Http\Requests\v1;

use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class MqopsExternalMeetingRequest extends FormRequest
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
            'partner_type' => 'required|exists:mqops_partner_types,id',
            'organisation' => 'nullable|exists:organisations,id',
            'files' => 'array|nullable',
            'team_members' => 'nullable|array|exists:users,id',
            'designation' => 'nullable',
            'contact_person' => 'nullable',
            'contact_person_count' => 'nullable',
            'summary' => 'nullable',
            'org_name' => 'nullable',
        ];
        return $rules;
    }
}
