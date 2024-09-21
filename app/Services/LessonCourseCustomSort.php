<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use App\Models\Course;
use Spatie\QueryBuilder\Sorts\Sort;

class LessonCourseCustomSort implements Sort
{
    public function __invoke(Builder $query, bool $descending, string $property)
    {
        $direction = $descending ? 'DESC' : 'ASC';
        return  $query->orderBy(
            Course::select('name as courseName')
                ->whereColumn('lessons.course_id', 'courses.id'),
            $direction
        );
    }
}
