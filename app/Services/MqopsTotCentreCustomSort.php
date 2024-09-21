<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use App\Models\CentreType;
use Spatie\QueryBuilder\Sorts\Sort;

class MqopsTotCentreCustomSort implements Sort
{
    public function __invoke(Builder $query, bool $descending, string $property)
    {
        $direction = $descending ? 'DESC' : 'ASC';
        return  $query->orderBy(
            CentreType::select('name as centreTypeName')
                ->whereColumn('mqops_tot_summary.centre_type_id', 'centre_types.id'),
            $direction
        );
    }
}
