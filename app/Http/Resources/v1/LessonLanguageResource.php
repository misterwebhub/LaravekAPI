<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Resources\Json\JsonResource;

class LessonLanguageResource extends JsonResource
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
            'language_id' => $this->pivot->language_id,
            'language' => $this->name,
            'folder_path' => $this->pivot->folder_path,
            'download_path' => $this->pivot->download_path,
            'index_path' => $this->pivot->index_path,
        ];
    }
}
