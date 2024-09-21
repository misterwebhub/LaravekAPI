<?php

namespace App\Http\Requests\v1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CentreRequest extends FormRequest
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
            'organisation' => 'required|exists:organisations,id',
            'centre_type' => 'required|exists:centre_types,id',
            'working_mode' => ['required', Rule::in(['0', '1', '2'])],
            'email' => 'max:125|email|regex:/^([a-z0-9_\.-]+)@([\da-z\.-]+)\.([a-z\.]{2,6})$/',
            'mobile' => 'nullable|digits:10|numeric',
            'website' => [
                'max:125', 'nullable',
                'regex:/^(?:https?\:\/\/|www\.)([a-z0-9\-]+\.)+[a-z]{2,4}(\.[a-z]{2,4})*(\/[^ ]+)*/'
            ],
            'address' => 'nullable',
            'pincode' => 'nullable|numeric|digits:6',
            'state' => 'required|exists:states,id',
            'district' => 'required|exists:districts,id',
            'city' => 'nullable|max:125',
            'location' => 'nullable|max:125',
            'partnership_type' => 'nullable|exists:partnership_types,id',
            'target_students' => 'nullable|numeric|digits_between:0,5',
            'target_trainers' => 'nullable|numeric|digits_between:0,5',

        ];
        if ($this->getMethod() == 'POST') {
            $rules += [
                'name' =>
                'required|max:125|unique:centres,name,NULL,NULL,deleted_at,NULL,tenant_id,'
                    . getTenant()
            ];
            $rules += [
                'center_id' =>
                'nullable|numeric|min:10000000000|max:99999999999|unique:centres,center_id,NULL,NULL,deleted_at,NULL,tenant_id,'
                    . getTenant()
            ];
        } else {
            $rules += [
                'name' =>
                'required|max:125|unique:centres,name,'
                    . $this->route('centre')->id . ',id,deleted_at,NULL,tenant_id,'
                    . getTenant()
            ];
            $rules += [
                'center_id' =>
                'nullable|numeric|min:10000000000|max:99999999999|unique:centres,center_id,'
                    . $this->route('centre')->id . ',id,deleted_at,NULL,tenant_id,'
                    . getTenant()
            ];
        }
        return $rules;
    }
}
