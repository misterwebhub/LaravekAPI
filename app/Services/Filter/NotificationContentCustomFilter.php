<?php

namespace App\Services\Filter;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class NotificationContentCustomFilter implements Filter
{
    public function __invoke(Builder $query, $value, string $property)
    {
        return $query->where(function ($p) use ($value) {
            $p->where('comment', 'LIKE', '%' . $value . '%')
                ->orWhere('content', 'LIKE', '%' . $value . '%')
                ->orWhere('key', 'LIKE', '%' . $value . '%');
        });
    }
}
