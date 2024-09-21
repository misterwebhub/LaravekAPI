<?php

namespace App\Http\Requests\v1;

use Illuminate\Foundation\Http\FormRequest;

class LessonImportRequest extends FormRequest
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
            'lesson_upload_file' => 'required|file|mimes:csv,txt',
        ];
    }
}
