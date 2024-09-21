<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use App\Models\FaqSubCategory;
use Spatie\QueryBuilder\Sorts\Sort;

class FaqSubCategoryCustomSort implements Sort
{
    public function __invoke(Builder $query, bool $descending, string $property)
    {
        $direction = $descending ? 'DESC' : 'ASC';
        return  $query->orderBy(
            FaqSubCategory::select('name as subCatName')
                ->whereColumn('faqs.faq_sub_category_id', 'faq_sub_categories.id'),
            $direction
        );
    }
}
