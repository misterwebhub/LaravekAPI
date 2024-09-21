<?php

namespace App\Http\Requests\v1;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\CheckAscendingOrder;

class BadgeRequest extends FormRequest
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
            'badges' => new CheckAscendingOrder()
        ];
        if ($this->input('badge_type') == 'performance_badges') {
            $rules += ['badges.*.point' => 'required|numeric|between:1,100'];
        } else {
            $rules += ['badges.*.point' => 'required|numeric'];
        }

        return $rules;
    }
}
