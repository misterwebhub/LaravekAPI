<?php

namespace App\Repositories\v1;

use App\Models\Faq;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use App\Services\FaqCategoryCustomSort;
use App\Services\FaqSubCategoryCustomSort;
use Spatie\QueryBuilder\AllowedSort;
use App\Services\Filter\FaqCustomFilter;
use App\Imports\FaqImport;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Class FaqRepository
 * @package App\Repositories
 */
class FaqRepository
{
    /**
     * List all faqs
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function index($request)
    {
        $faqs = QueryBuilder::for(Faq::class)
            ->with('category', 'subCategory')
            ->allowedFilters(
                [
                    'title', 'category.name', 'subCategory.name', 'description',
                    AllowedFilter::exact('faq_category_id'),
                    AllowedFilter::custom('search_value', new FaqCustomFilter()),
                ]
            )
            ->allowedSorts(
                [
                    'title', 'description',
                    AllowedSort::custom('faq.category', new FaqCategoryCustomSort()),
                    AllowedSort::custom('faq.sub_category', new FaqSubCategoryCustomSort()),
                ]
            )
            ->where('faqs.tenant_id', getTenant())
            ->latest()
            ->paginate($request['limit'] ?? null);
        return $faqs;
    }
    /**
     * Create a new Faq
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function store($request)
    {
        $faq = new Faq();
        $faq = $this->setFaq($request, $faq);
        $faq->save();
        return $faq;
    }

    /**
     * Delete an Faq
     * @param mixed $id
     *
     * @return [type]
     */
    public function destroy($faq)
    {
        $faq->delete();
    }

    /**
     * Update Faq
     * @param mixed $request
     * @param mixed $id
     *
     * @return [json]
     */
    public function update($request, $faq)
    {
        $faq = $this->setFaq($request, $faq);
        $faq->update();
        return $faq;
    }
    /**
     * Set faq Data
     * @param mixed $request
     * @param mixed $faq
     *
     * @return [collection]
     */
    private function setFaq($request, $faq)
    {
        $faq->title = $request['title'];
        $faq->description = $request['description'];
        $faq->faq_category_id = $request['category'];
        $faq->faq_sub_category_id = $request['sub_category'];
        $faq->tenant_id = getTenant();
        return $faq;
    }

    /**
     * Import Faqs
     *
     * @param mixed $request
     *
     * @return [type]
     */
    public function importFaqs($request)
    {
        $import = new FaqImport();
        Excel::import($import, $request['faq_upload_file']);
        return $import->data;
    }
}
