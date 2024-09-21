<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use App\Models\Centre;
use Spatie\QueryBuilder\Sorts\Sort;

class FacilitatorStateCustomSort implements Sort
{
    public function __invoke(Builder $query, bool $descending, string $property)
    {
        $direction = $descending ? 'DESC' : 'ASC';
        return  $query->orderBy(
            Centre::join('states', 'centres.state_id', 'states.id')->select('states.name as state')
                ->whereColumn('centres.state_id', 'states.id')
                ->whereColumn('users.centre_id', 'centres.id'),
            $direction
        );
    }
}
