<?php

namespace App\Http\Requests\v1;

use Illuminate\Foundation\Http\FormRequest;

class ProjectRequest extends FormRequest
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
            'program' => 'required|exists:programs,id',
            'phase' => 'array|exists:phases,id'
        ];
        if ($this->getMethod() == 'POST') {
            $rules += [
                'name' =>
                'required|max:125|unique:projects,name,NULL,NULL,deleted_at,NULL,tenant_id,'
                    . getTenant()
            ];
        } else {
            $rules += [
                'name' =>
                'required|max:125|unique:projects,name,' . $this->route('project')->id .
                    ',id,deleted_at,NULL,tenant_id,' . getTenant()
            ];
        }
        return $rules;
    }
}
