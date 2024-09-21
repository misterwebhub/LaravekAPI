<?php

namespace App\Http\Requests\v1;

use Illuminate\Foundation\Http\FormRequest;

class StudentBatchRequest extends FormRequest
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
            'id' => 'required',
            'educational_qualification_id' => 'nullable|exists:educational_qualifications,id',
            'trade_id' => 'nullable|exists:trades,id',
            'last_monthly_salary' => 'nullable|numeric',
            'updated_email' => 'nullable|email|regex:/^([a-z0-9_\.-]+)@([\da-z\.-]+)\.([a-z\.]{2,6})$/',
            'contactability' => 'nullable',
            'interview1_company_name' => 'nullable',
            'interview1_date' => 'nullable',
            'interview1_result' => 'nullable',
            'placed' => 'nullable',
            'date_of_updation' => 'nullable',
        ];
    }
}
