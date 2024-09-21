<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use App\Models\Centre;
use Spatie\QueryBuilder\Sorts\Sort;

class StudentCentreCustomSort implements Sort
{
    public function __invoke(Builder $query, bool $descending, string $property)
    {
        $direction = $descending ? 'DESC' : 'ASC';
        return  $query->orderBy(
            Centre::select('name as orgName')
                ->whereColumn('users.centre_id', 'centres.id'),
            $direction
        );
    }
}
