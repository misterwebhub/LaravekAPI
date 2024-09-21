<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use App\Models\MqopsActivityType;
use Spatie\QueryBuilder\Sorts\Sort;

class MqopsActivityTypeCustomSort implements Sort
{
    public function __invoke(Builder $query, bool $descending, string $property)
    {
        $direction = $descending ? 'DESC' : 'ASC';
        return  $query->orderBy(
            MqopsActivityType::select('name as typeName')
                ->whereColumn('mqops_activity_trackers.mqops_activity_type_id', 'mqops_activity_types.id'),
            $direction
        );
    }
}
