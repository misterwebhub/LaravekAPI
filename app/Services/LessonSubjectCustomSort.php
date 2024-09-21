<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use App\Models\Subject;
use Spatie\QueryBuilder\Sorts\Sort;

class LessonSubjectCustomSort implements Sort
{
    public function __invoke(Builder $query, bool $descending, string $property)
    {
        $direction = $descending ? 'DESC' : 'ASC';
        return  $query->orderBy(
            Subject::select('name as subjectName')
                ->whereColumn('lessons.subject_id', 'subjects.id'),
            $direction
        );
    }
}
