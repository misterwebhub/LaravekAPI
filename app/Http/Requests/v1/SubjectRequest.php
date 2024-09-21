<?php

namespace App\Http\Requests\v1;

use Illuminate\Foundation\Http\FormRequest;

class SubjectRequest extends FormRequest
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
            'tag' => 'required|max:125',
            'description' => 'required',
            'image' => 'required'
        ];
        if ($this->getMethod() == 'POST') {
            $rules += [
                'name' =>
                'required|max:125|unique:subjects,name,NULL,NULL,deleted_at,NULL,tag,"'
                    . $this->tag . '",tenant_id,' . getTenant()
            ];
        } else {
            $rules += [
                'name' =>
                'required|max:125|unique:subjects,name,'
                    . $this->route('subject')->id
                    . ',id,deleted_at,NULL,tag,"'
                    . $this->tag . '",tenant_id,' . getTenant()
            ];
        }
        return $rules;
    }
}
