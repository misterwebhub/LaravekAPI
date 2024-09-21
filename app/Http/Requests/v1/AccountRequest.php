<?php

namespace App\Http\Requests\v1;

use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class AccountRequest extends FormRequest
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
            'name' => 'required',
            'account_type' => 'required',
            'is_quest_employee' => ['required', Rule::in([1, 2])],
        ];
        if ($this->input('account_type') == 'organisation-head') {
            $rules += ['organisation_id' => 'required|exists:organisations,id'];
        } elseif ($this->input('account_type') == 'centre-head') {
            $rules += ['centre_id' => 'required|exists:centres,id'];
        } elseif (in_array($this->input('account_type'), array("program-head", "program-team-member"))) {
            $rules += ['program_id' => 'required|exists:programs,id'];
        } elseif (
            in_array(
                $this->input('account_type'),
                array("project-head", "project-team-member", "project-funder")
            )
        ) {
            $rules += ['project_id' => 'required|exists:projects,id'];
        }

        if ($this->getMethod() == 'POST') {
            $rules += ['email' => 'required|email|regex:/^([a-z0-9_\.-]+)@([\da-z\.-]+)\.([a-z\.]{2,6})$/
            |unique:users,email,NULL,NULL,deleted_at,NULL,type,'
                . User::TYPE_ADMIN];
            $rules += ['password' => 'required|min:6'];
        } else {
            $rules += ['email' => 'required|email|regex:/^([a-z0-9_\.-]+)@([\da-z\.-]+)\.([a-z\.]{2,6})$/
            |unique:users,email,' . $this->route('account')->id . ',id,deleted_at,NULL,type,'
                . User::TYPE_ADMIN];
            $rules += ['password' => 'nullable|min:6'];
        }
        return $rules;
    }
}
