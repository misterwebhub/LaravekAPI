<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use App\Models\SessionType;
use Spatie\QueryBuilder\Sorts\Sort;

class MqopsSessionTypeCustomSort implements Sort
{
    public function __invoke(Builder $query, bool $descending, string $property)
    {
        $direction = $descending ? 'DESC' : 'ASC';
        return  $query->orderBy(
            SessionType::select('name as typeName')
                ->whereColumn('mqops_session_trackers.session_type_id', 'session_types.id'),
            $direction
        );
    }
}
