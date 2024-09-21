<?php

namespace App\Http\Requests\v1;

use Illuminate\Foundation\Http\FormRequest;

class LessonFeedbackRequest extends FormRequest
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
            'lesson_id' => 'required',
            // 'course_id' => 'required',
            // 'subject_id' => 'required',            
            'feedback' => 'required|max:250',
           
        ];
        return $rules;
    }
}
