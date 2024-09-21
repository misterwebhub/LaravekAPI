<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Resources\Json\JsonResource;

class PointResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        if (!$this->title) {
            return parent::toArray($request);
        }
        return [
            'id' => $this->id,
            'title' => $this->title,
            'point' => $this->point,
        ];
    }
}
