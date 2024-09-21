<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Resources\Json\JsonResource;

class FaqResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'faq_category' => $this->category->name ?? NULL,
            'faq_category_id' => $this->faq_category_id,
            'faq_sub_category' => $this->subCategory->name ?? NULL,
            'faq_sub_category_id' => $this->faq_sub_category_id
        ];
    }
}
