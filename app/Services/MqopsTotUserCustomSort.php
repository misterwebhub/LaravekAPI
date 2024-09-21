<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use App\Models\User;
use Spatie\QueryBuilder\Sorts\Sort;

class MqopsTotUserCustomSort implements Sort
{
    public function __invoke(Builder $query, bool $descending, string $property)
    {
        $direction = $descending ? 'DESC' : 'ASC';
        return  $query->orderBy(
            User::select('name as userName')
                ->whereColumn('mqops_tot_summary.user_id', 'users.id'),
            $direction
        );
    }
}
