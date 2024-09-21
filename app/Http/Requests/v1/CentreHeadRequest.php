<?php

namespace App\Http\Requests\v1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\User;

class CentreHeadRequest extends FormRequest
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
        ];
        if ($this->getMethod() == 'POST') {
            $rules += ['email' => "required|email|regex:/^([a-z0-9_\.-]+)@([\da-z\.-]+)\.([a-z\.]{2,6})$/
            |" . Rule::unique('users')->where('type', User::TYPE_ADMIN)->WhereNull('deleted_at')];
            $rules += ['password' => 'required|min:6'];
        } else {
            $rules += ['email' => "required|email|regex:/^([a-z0-9_\.-]+)@([\da-z\.-]+)\.([a-z\.]{2,6})$/
            |" . Rule::unique('users')->where('type', User::TYPE_ADMIN)->WhereNull('deleted_at')
                    ->ignore($this->route('centre_head')->id)];
            $rules += ['password' => 'nullable|min:6'];
        }
        return $rules;
    }
}
