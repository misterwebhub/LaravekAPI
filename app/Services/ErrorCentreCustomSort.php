<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use App\Models\Centre;
use App\Models\User;
use Spatie\QueryBuilder\Sorts\Sort;

class ErrorCentreCustomSort implements Sort
{
    public function __invoke(Builder $query, bool $descending, string $property)
    {
        $direction = $descending ? 'DESC' : 'ASC';
        return $query->orderBy(
            Centre::select('centres.name')->whereHas('users', function ($q) {
                $q->whereColumn('users.centre_id', 'centres.id')
                ->whereColumn('error_report_view.user_id', 'users.id');
            }),
            $direction
        );
    }
}
