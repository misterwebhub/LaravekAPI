<?php

namespace App\Http\Requests\v1;

use Illuminate\Foundation\Http\FormRequest;

class LessonLinkRequest extends FormRequest
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
            'link' => 'required|array',
            'link.*.name' => 'nullable|max:255',
            'link.*.folder_path' => 'nullable',
            'link.*.download_path' => 'nullable',
            'link.*.index_path' => 'nullable',
        ];
        return $rules;
    }
}
