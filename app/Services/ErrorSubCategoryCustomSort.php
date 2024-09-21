<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use App\Models\FaqSubCategory;
use Spatie\QueryBuilder\Sorts\Sort;

class ErrorSubCategoryCustomSort implements Sort
{
    public function __invoke(Builder $query, bool $descending, string $property)
    {
        $direction = $descending ? 'DESC' : 'ASC';
        return  $query->orderBy(
            FaqSubCategory::select('name as subCatName')
                ->whereColumn('error_report_view.faq_sub_category_id', 'faq_sub_categories.id'),
            $direction
        );
    }
}
