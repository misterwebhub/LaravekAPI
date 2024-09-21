<?php

namespace App\Services\Filter;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class SubjectCustomFilter implements Filter
{
    public function __invoke(Builder $query, $value, string $property)
    {
        return $query->where(function ($p) use ($value) {
            $p->where('name', 'LIKE', '%' . $value . '%')
                ->orWhereHas('projects', function ($q) use ($value) {
                    $q->where('name', 'LIKE', '%' . $value . '%');
                });
        });
    }
}
