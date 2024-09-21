<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Resources\Json\JsonResource;

class LanguageResource extends JsonResource
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
            'language_id' => $this->id,
            'language' => $this->name,
            'folder_path' => "",
            'download_path' => "",
            'index_path' => "",
        ];
    }
}
