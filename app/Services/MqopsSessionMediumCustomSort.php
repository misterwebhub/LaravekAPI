<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use App\Models\MqopsActivityMedium;
use Spatie\QueryBuilder\Sorts\Sort;

class MqopsSessionMediumCustomSort implements Sort
{
    public function __invoke(Builder $query, bool $descending, string $property)
    {
        $direction = $descending ? 'DESC' : 'ASC';
        return  $query->orderBy(
            MqopsActivityMedium::select('name as mediumName')
                ->whereColumn('mqops_session_trackers.mqops_activity_medium_id', 'mqops_activity_mediums.id'),
            $direction
        );
    }
}
