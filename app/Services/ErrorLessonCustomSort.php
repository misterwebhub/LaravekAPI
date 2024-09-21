<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use App\Models\Lesson;
use Spatie\QueryBuilder\Sorts\Sort;

class ErrorLessonCustomSort implements Sort
{
    public function __invoke(Builder $query, bool $descending, string $property)
    {
        $direction = $descending ? 'DESC' : 'ASC';
        return  $query->orderBy(
            Lesson::select('name as lessonName')
                ->whereColumn('error_report_view.lesson_id', 'lessons.id'),
            $direction
        );
    }
}
