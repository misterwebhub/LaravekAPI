<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use App\Models\State;
use Spatie\QueryBuilder\Sorts\Sort;

class MqopsTotStateCustomSort implements Sort
{
    public function __invoke(Builder $query, bool $descending, string $property)
    {
        $direction = $descending ? 'DESC' : 'ASC';
        return  $query->orderBy(
            State::select('name as stateName')
                ->whereColumn('mqops_tot_summary.state_id', 'states.id'),
            $direction
        );
    }
}
