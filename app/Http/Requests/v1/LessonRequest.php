<?php

namespace App\Http\Requests\v1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;

class LessonRequest extends FormRequest
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
    public function rules(Request $request)
    {
        $subjectId = $request->subject;
        $rules = [
            'course' => 'nullable|exists:courses,id',
            'subject' => 'required|exists:subjects,id',
            'lesson_type' => 'required|exists:lesson_types,id',
            'lesson_category' => 'required|exists:lesson_categories,id',
            'point' => 'nullable|numeric',
            'lesson_order' => 'nullable|numeric',
            'is_mandatory' => 'required|numeric',
            'tag' => 'required',
            'resource_url' => 'required',
            'language_id' => 'required',
            //    'lesson_category' => 'required'

        ];
        if ($this->getMethod() == 'POST') {
            $rules += [
                'name' => 'required||max:100|
            unique:lessons,name,NULL,NULL,deleted_at,NULL,subject_id,' . $subjectId
            ];
        } else {
            $rules += [
                'name' => 'required||max:100|
            unique:lessons,name,' . $this->route('lesson')->id . ',id,deleted_at,NULL,subject_id,'
                    . $this->route('lesson')->subject_id
            ];
        }
        return $rules;
    }
}
