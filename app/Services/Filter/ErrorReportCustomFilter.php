<?php

namespace App\Services\Filter;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class ErrorReportCustomFilter implements Filter
{
    public function __invoke(Builder $query, $value, string $property)
    {
        return $query->where(function ($p) use ($value) {
            $p->where('lesson_name', 'LIKE', '%' . $value . '%')
                ->orwhere('category_name', 'LIKE', '%' . $value . '%')
                ->orwhere('subcategory_name', 'LIKE', '%' . $value . '%')
                ->orwhere('subject_name', 'LIKE', '%' . $value . '%')
                ->orwhere('email', 'LIKE', '%' . $value . '%')
                ->orwhere('mobile', 'LIKE', '%' . $value . '%')
                ->orwhere('centre_name', 'LIKE', '%' . $value . '%');
        });
    }
}
