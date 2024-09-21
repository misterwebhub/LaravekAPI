<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use App\Models\User;
use Spatie\QueryBuilder\Sorts\Sort;

class ErrorProfileCustomSort implements Sort
{
    public function __invoke(Builder $query, bool $descending, string $property)
    {
        $direction = $descending ? 'DESC' : 'ASC';
        return  $query->orderBy(
            User::select('name as catName')
                ->whereColumn('error_report_view.user_id', 'users.id'),
            $direction
        );
    }
}
