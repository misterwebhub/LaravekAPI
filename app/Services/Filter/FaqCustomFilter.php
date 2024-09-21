<?php

namespace App\Services\Filter;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class FaqCustomFilter implements Filter
{
    public function __invoke(Builder $query, $value, string $property)
    {
        return $query->where(function ($p) use ($value) {
            $p->where('title', 'LIKE', '%' . $value . '%')
                ->orWhere('description', 'LIKE', '%' . $value . '%')
                ->orWhereHas('category', function ($q) use ($value) {
                    $q->where('name', 'LIKE', '%' . $value . '%');
                })->orWhereHas('subCategory', function ($q) use ($value) {
                    $q->where('name', 'LIKE', '%' . $value . '%');
                });
        });
    }
}
