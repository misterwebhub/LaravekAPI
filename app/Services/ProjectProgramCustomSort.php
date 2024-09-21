<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use App\Models\Program;
use Spatie\QueryBuilder\Sorts\Sort;

class ProjectProgramCustomSort implements Sort
{
    public function __invoke(Builder $query, bool $descending, string $property)
    {
        $direction = $descending ? 'DESC' : 'ASC';
        return  $query->orderBy(
            Program::select('name as programName')
                ->whereColumn('projects.program_id', 'programs.id'),
            $direction
        );
    }
}
