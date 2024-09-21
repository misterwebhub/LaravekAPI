<?php

namespace App\Http\Requests\v1;

use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class MqopsTeamActivityRequest extends FormRequest
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
            'type' => ['required', Rule::in([ 'careerclub', 'placementofficer', 'employer', 'careerplace', 'careerday','careerclub_session', 'support_session', 'counscelling_session', 'es_curriculam', 'bootcamp', 'ecosystem', 'mode'])],
        ];
        return $rules;
    }
}
