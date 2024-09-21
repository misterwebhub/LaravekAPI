<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use App\Models\Subject;
use Spatie\QueryBuilder\Sorts\Sort;

class SubjectCentreCountCustomSort implements Sort
{
    public function __invoke(Builder $query, bool $descending, string $property)
    {
        $direction = $descending ? 'DESC' : 'ASC';
        return  $query->withCount('centres')->orderBy('centres_count',
            $direction
        );
    }
}