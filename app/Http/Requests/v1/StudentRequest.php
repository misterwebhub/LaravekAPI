<?php

namespace App\Http\Requests\v1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\User;

class StudentRequest extends FormRequest
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
            'gender' => 'required|in:male,female,other',
            'organisation_id' => 'required|exists:organisations,id',
            'centre_id' => 'required|exists:centres,id',
            'batch_id' => 'exists:batches,id',
            'qualification' => 'nullable|exists:educational_qualifications,id',
            'trade_id' => 'nullable|exists:trades,id',
            'guardian_mobile' => 'nullable|digits:10|numeric',
            'guardian_email' => 'nullable|regex:/^([a-z0-9_\.-]+)@([\da-z\.-]+)\.([a-z\.]{2,6})$/',
        ];
        if ($this->getMethod() == 'POST') {
            $rules += ['email' => "required_without:mobile|email
            |regex:/^([a-z0-9_\.-]+)@([\da-z\.-]+)\.([a-z\.]{2,6})$/|nullable
            |" . Rule::unique('users')->whereNot('type', User::TYPE_ADMIN)
                ->whereNotNull('email')->WhereNull('deleted_at')];
            $rules += ['mobile' => "required_without:email|nullable|digits:10|numeric
            |" . Rule::unique('users')->whereNot('type', User::TYPE_ADMIN)
                ->whereNotNull('mobile')->WhereNull('deleted_at')];
            $rules += ['password' => 'required|min:6',];
            $rules += ['batch_id' => 'nullable|exists:batches,id'];
        } else {
            $rules += ['email' => "required_without:mobile|email
            |regex:/^([a-z0-9_\.-]+)@([\da-z\.-]+)\.([a-z\.]{2,6})$/|nullable
            |" . Rule::unique('users')->whereNot('type', User::TYPE_ADMIN)
                ->whereNotNull('email')->WhereNull('deleted_at')
                ->ignore($this->route('student')->id)];
            $rules += ['mobile' => "required_without:email|nullable|digits:10|numeric
            |" . Rule::unique('users')->whereNot('type', User::TYPE_ADMIN)
                ->whereNotNull('mobile')->WhereNull('deleted_at')
                ->ignore($this->route('student')->id)];
            $rules += ['password' => 'nullable|min:6',];
        }
        return $rules;
    }
}
