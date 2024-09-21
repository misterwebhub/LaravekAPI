<?php

namespace App\Imports;

use App\Models\FaqCategory;
use App\Models\FaqSubCategory;
use App\Models\Faq;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Class OrganisationDataImport
 * @package App\Imports
 */
class FaqImport implements ToCollection, WithHeadingRow
{
    public $data;

    /**
     * @param Collection $collection
     */
    public function collection(Collection $collection)
    {
        foreach ($collection->chunk(10) as $chunk) {

            foreach ($chunk as $row) {
                $faqDetails = $this->fetchdata($row);
                if ($row['title']) {

                    $faq = Faq::where('faq_category_id', $faqDetails['faq_category_id'])->where('title', $faqDetails['title'])->first();
                    if ($faq) {
                        $faq = $this->setFaq($faq, $faqDetails);
                        $faq->update();
                    } else {
                        $faq = new Faq();
                        $faq = $this->setFaq($faq, $faqDetails);
                        $faq->save();
                    }
                }
            }
        }
        $data['status'] = 1;
        $data['message'] = trans('admin.file_imported');
        $this->data = $data;
    }

    private function fetchdata($row)
    {
        $faqCategories = FaqCategory::all();
        $faqSubCategories = FaqSubCategory::all();

        $faqDetails['category'] = trim($row['category']);
        $faqDetails['subcategory'] = trim($row['subcategory']);
        $faqCategoryDet = $this->getCollectionCaseInsensitiveString(
            $faqCategories,
            'name',
            trim($row['category'])
        )->first();
        $faqDetails['faq_category_id'] = $faqCategoryDet->id ?? "";

        $faqSubCategoryDet = $this->getCollectionCaseInsensitiveString(
            $faqSubCategories,
            'name',
            trim($row['subcategory'])
        )->first();
        if(empty($faqSubCategoryDet->id))
        {
            $faqSubcategory = new FaqSubCategory();
            $faqSubcategory = $this->setFaqSubcategory($faqSubcategory, $faqDetails);
            $faqSubcategory->save();
        }
        $faqDetails['faq_sub_category_id'] = $faqSubCategoryDet->id ?? $faqSubcategory->id;
        $faqDetails['title'] =  trim($row['title']) ?? null;
        $faqDetails['description'] = trim($row['description']) ?? null;

        return $faqDetails;
    }
    private function getCollectionCaseInsensitiveString($collection, $attribute, $value)
    {
        $collection = $collection->filter(function ($item) use ($attribute, $value) {
            return strtolower($item[$attribute]) == strtolower($value);
        });
        return $collection;
    }
    private function setFaq($faq, $faqDetails)
    {
        $faq->title = $faqDetails['title'];
        $faq->description = $faqDetails['description'];
        $faq->faq_category_id = $faqDetails['faq_category_id'];
        $faq->faq_sub_category_id = $faqDetails['faq_sub_category_id'];
        $faq->tenant_id = "a287c91d-c8ff-4448-8d96-906d4654b6f2";
        return $faq;
    }

    private function setFaqSubcategory($faqSubcategory, $faqDetails)
    {
        $faqSubcategory->name = $faqDetails['subcategory'];
        $faqSubcategory->faq_category_id = $faqDetails['faq_category_id'];
        $faqSubcategory->tenant_id = "a287c91d-c8ff-4448-8d96-906d4654b6f2";
        $faqSubcategory->status = 1;
        return $faqSubcategory;
    }
}
