<?php

namespace App\Http\Requests\v1;

use Illuminate\Foundation\Http\FormRequest;

class PhaseRequest extends FormRequest
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
            'target_students' => 'nullable|numeric',
            'target_trainers' => 'nullable|numeric',
            'phases' => 'nullable',
        ];
        if ($this->getMethod() == 'POST') {
            $rules += [
                'name' =>
                'required|max:125|unique:phases,name,NULL,NULL,deleted_at,NULL,tenant_id,'
                    . getTenant()
            ];
        } else {
            $rules += [
                'name' =>
                'required|max:125|unique:phases,name,'
                    . $this->route('phase')->id . ',id,deleted_at,NULL,tenant_id,'
                    . getTenant()
            ];
        }
        return $rules;
    }
}
