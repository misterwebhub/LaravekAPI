<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use App\Models\FaqCategory;
use Spatie\QueryBuilder\Sorts\Sort;

class ErrorCategoryCustomSort implements Sort
{
    public function __invoke(Builder $query, bool $descending, string $property)
    {
        $direction = $descending ? 'DESC' : 'ASC';
        return  $query->orderBy(
            FaqCategory::select('name as catName')
                ->whereColumn('error_report_view.faq_category_id', 'faq_categories.id'),
            $direction
        );
    }
}
