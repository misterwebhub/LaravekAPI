<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Resources\Json\JsonResource;

class ResourceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        if (!$this->name) {
            return parent::toArray($request);
        }
        return [
            'id' => $this->id,
            'name' => $this->name,
            'resource_category_id' => $this->resource_category_id,
            'resource_category' => $this->resourceCategory->name ?? null,
            'link' => $this->link,
            'point' => $this->point,
            'status' => $this->status,
            'course_id' => $this->course_id,
            'course' => $this->course->name ?? null,
            'subject_id' => $this->subject_id,
            'subject' => $this->subject->name ?? null,
            'category_name' => $this->category_name
        ];
    }
}
