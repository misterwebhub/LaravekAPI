<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use App\Models\FaqCategory;
use Spatie\QueryBuilder\Sorts\Sort;

class FaqCategoryCustomSort implements Sort
{
    public function __invoke(Builder $query, bool $descending, string $property)
    {
        $direction = $descending ? 'DESC' : 'ASC';
        return  $query->orderBy(
            FaqCategory::select('name as orgName')
                ->whereColumn('faqs.faq_category_id', 'faq_categories.id'),
            $direction
        );
    }
}
