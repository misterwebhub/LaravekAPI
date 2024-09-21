<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class CheckAscendingOrder implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        for( $j=0;  $j < count($value)-1; $j++)
        {
          if ( $value[$j]["point"] > $value[$j+1]["point"] )
          {
            return false;
          }
        }
        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return trans('admin.invalid_array_order');
    }
}
