<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use App\Models\Sector;
use Spatie\QueryBuilder\Sorts\Sort;

class PlacementSectorCustomSort implements Sort
{
    public function __invoke(Builder $query, bool $descending, string $property)
    {
        $direction = $descending ? 'DESC' : 'ASC';
        return  $query->orderBy(
            Sector::select('name as sectorName')
                ->whereColumn('placements.sector_id', 'sectors.id'),
            $direction
        );
    }
}
