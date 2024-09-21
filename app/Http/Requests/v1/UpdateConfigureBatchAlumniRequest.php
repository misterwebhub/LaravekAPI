<?php

namespace App\Http\Requests\v1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Centre;

class UpdateConfigureBatchAlumniRequest extends FormRequest
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
            'centre_id' => 'required|exists:centres,id',
            'configure_batch_alumni' => ['required', Rule::in([1, 2])],
            'batch_end_interval' => 'required_if:configure_batch_alumni,' . Centre::TYPE_CONFIGURE_YEAR_OF_REGISTRATION
        ];
    }

    /**
    * Get the error messages for the defined validation rules.
    *
    * @return array
    */
    public function messages()
    {
        return [
            'batch_end_interval.required_if' => trans('validation.batch_end_interval_required_if'),
        ];
    }
}
