<?php

namespace App\Services\Filter;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class PlacementCustomFilter implements Filter
{
    public function __invoke(Builder $query, $value, string $property)
    {
        return $query->where(function ($p) use ($value) {
            $p->where('company', 'LIKE', '%' . $value . '%')
                ->orWhere('designation', 'LIKE', '%' . $value . '%')
                ->orWhereHas('sector', function ($q) use ($value) {
                    $q->where('name', 'LIKE', '%' . $value . '%');
                });
        });
    }
}
