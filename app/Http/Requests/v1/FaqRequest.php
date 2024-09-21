<?php

namespace App\Http\Requests\v1;

use Illuminate\Foundation\Http\FormRequest;

class FaqRequest extends FormRequest
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
            'description' => 'required',
            'category' => 'required|exists:faq_categories,id',
            'sub_category' => 'required|exists:faq_sub_categories,id',
        ];
        if ($this->getMethod() == 'POST') {
            $rules += ['title' => 'required|max:125|
            unique:faqs,title,NULL,NULL,deleted_at,NULL'];
        } else {
            $rules += ['title' => 'required|max:125|
            unique:faqs,title,' . $this->route('faq')->id . ',id,deleted_at,NULL'];
        }
        return $rules;
    }
}
