<?php

namespace App\Services\Filter;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;
use App\Models\Project;

class StudentProjectCustomFilter implements Filter
{
    public function __invoke(Builder $query, $value, string $property)
    {
        if ($value != null) {
            return $query
                //->whereIn('users.centre_id', Project::find($value)->centres->pluck('id')->toArray())
                ->join('centres', 'users.centre_id', '=', 'centres.id')
                ->join('centre_project', 'centres.id', '=', 'centre_project.centre_id')
                ->join('projects', 'projects.id', '=', 'centre_project.project_id')
                ->join('programs', 'programs.id', '=', 'projects.program_id')
                ->where('projects.id', $value)
                ->select('users.*', 'programs.name as program_name', 'projects.id as project_id','projects.name as project_name');
        }
    }
}
