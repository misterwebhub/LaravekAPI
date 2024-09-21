<?php

namespace App\Http\Requests\v1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleRequest extends FormRequest
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
        return [
            'name' => 'required|max:100|regex:/(^[A-Za-z ]+$)+/',
            'status' => 'in:0,1',
            'need_approval' => 'required|in:0,1',
            'permission_id' => 'exists:permissions,id,type,1',
            'description' => 'nullable|max:100'
        ];
    }
}
