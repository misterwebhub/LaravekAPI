<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use App\Models\MqopsTotType;
use Spatie\QueryBuilder\Sorts\Sort;

class MqopsTotTypeCustomSort implements Sort
{
    public function __invoke(Builder $query, bool $descending, string $property)
    {
        $direction = $descending ? 'DESC' : 'ASC';
        return  $query->orderBy(
            MqopsTotType::select('name as typeName')
                ->whereColumn('mqops_tot_summary.tot_is', 'mqops_tot_types.id'),
            $direction
        );
    }
}
