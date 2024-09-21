<?php

namespace App\Services\Filter;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class CentreHeadCustomFilter implements Filter
{
    public function __invoke(Builder $query, $value, string $property)
    {
        return $query->where(function ($p) use ($value) {
            $p->where('name', 'LIKE', '%' . $value . '%')
            ->orWhere('mobile', 'LIKE', '%' . $value . '%')
            ->orWhere('status', 'LIKE', '%' . $value . '%')
            ->orWhere('email', 'LIKE', '%' . $value . '%');
        });
    }
}
