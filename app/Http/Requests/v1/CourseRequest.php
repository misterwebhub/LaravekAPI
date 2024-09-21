<?php

namespace App\Http\Requests\v1;

use Illuminate\Foundation\Http\FormRequest;

class CourseRequest extends FormRequest
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
            'subject' => 'required|exists:subjects,id',
            'order' => 'nullable|numeric'
        ];
        if ($this->getMethod() == 'POST') {
            $rules += ['name' =>
            'required|max:125|unique:courses,name,NULL,NULL,deleted_at,NULL,tenant_id,'
                . getTenant()];
        } else {
            $rules += ['name' =>
            'required|max:125|unique:courses,name,' . $this->route('course')->id .
                ',id,deleted_at,NULL,tenant_id,' . getTenant()];
        }
        return $rules;
    }
}
