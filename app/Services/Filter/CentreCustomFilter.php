<?php

namespace App\Services\Filter;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class CentreCustomFilter implements Filter
{
    public function __invoke(Builder $query, $value, string $property)
    {
        return $query->where(function ($p) use ($value) {
            $p->where('centres.name', 'LIKE', '%' . $value . '%')
                ->orWhere('centres.email', 'LIKE', '%' . $value . '%')
                ->orWhere('centres.mobile', 'LIKE', '%' . $value . '%')
                ->orWhereHas('organisation', function ($q) use ($value) {
                    $q->where('name', 'LIKE', '%' . $value . '%');
                });
        });
    }
}
