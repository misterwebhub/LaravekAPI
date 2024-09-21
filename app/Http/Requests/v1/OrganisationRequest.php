<?php

namespace App\Http\Requests\v1;

use Illuminate\Foundation\Http\FormRequest;

class OrganisationRequest extends FormRequest
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
            'email' => 'required|max:125|email|regex:/^([a-z0-9_\.-]+)@([\da-z\.-]+)\.([a-z\.]{2,6})$/',
            'mobile' => 'nullable|digits:10|numeric',
            'website' => ['max:125', 'nullable', 'regex:/^((?:https?\:\/\/|www\.)(?:[-a-z0-9]+\.)*[-a-z0-9]+.*)$/'],
            'address' => 'nullable',
            'pincode' => 'nullable|numeric|digits:6',
            'state' => 'nullable|exists:states,id',
            'district' => 'nullable|exists:districts,id',
            'city' => 'nullable|max:125',
            'program' => 'array',
            'program.*' => 'nullable|exists:programs,id'
        ];
        if ($this->getMethod() == 'POST') {
            $rules += [
                'name' =>
                'required|max:125|unique:organisations,name,NULL,NULL,deleted_at,NULL,tenant_id,'
                    . getTenant()
            ];
        } else {
            $rules += [
                'name' =>
                'required|max:125|unique:organisations,name,'
                    . $this->route('organisation')->id . ',id,deleted_at,NULL,tenant_id,'
                    . getTenant()
            ];
        }
        return $rules;
    }
}
