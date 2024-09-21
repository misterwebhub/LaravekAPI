<?php

namespace App\Services\Filter;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class FacilitatorCustomFilter implements Filter
{
    public function __invoke(Builder $query, $value, string $property)
    {
        return $query->where(function ($p) use ($value) {
            $p->where('users.name', 'LIKE', '%' . $value . '%')
                ->orWhere('users.email', 'LIKE', '%' . $value . '%')
                ->orWhere('users.mobile', 'LIKE', '%' . $value . '%');
        });
    }
}
