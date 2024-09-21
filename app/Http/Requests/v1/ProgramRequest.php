<?php

namespace App\Http\Requests\v1;

use Illuminate\Foundation\Http\FormRequest;

class ProgramRequest extends FormRequest
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
        $rules = [];
        if ($this->getMethod() == 'POST') {
            $rules += [
                'name' =>
                'required|max:255|unique:programs,name,NULL,NULL,deleted_at,NULL,tenant_id,'
                    . getTenant()
            ];
        } else {
            $rules += [
                'name' =>
                'required|max:255|unique:programs,name,'
                    . $this->route('program')->id . ',id,deleted_at,NULL,tenant_id,'
                    . getTenant()
            ];
        }
        return $rules;
    }
}
