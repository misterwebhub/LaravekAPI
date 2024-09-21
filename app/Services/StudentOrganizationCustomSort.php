<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use App\Models\Organisation;
use Spatie\QueryBuilder\Sorts\Sort;

class StudentOrganizationCustomSort implements Sort
{
    public function __invoke(Builder $query, bool $descending, string $property)
    {
        $direction = $descending ? 'DESC' : 'ASC';
        return  $query->orderBy(
            Organisation::select('name as orgName')
                ->whereColumn('users.organisation_id', 'organisations.id'),
            $direction
        );
    }
}
