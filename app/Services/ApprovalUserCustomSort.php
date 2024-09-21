<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use App\Models\User;
use Spatie\QueryBuilder\Sorts\Sort;

class ApprovalUserCustomSort implements Sort
{
    public function __invoke(Builder $query, bool $descending, string $property)
    {
        $direction = $descending ? 'DESC' : 'ASC';
        return  $query->orderBy(
            User::select('name as userName')
                ->whereColumn('approvals.created_by', 'users.id'),
            $direction
        );
    }
}
