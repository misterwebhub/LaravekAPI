<?php

namespace App\Http\Resources\v1;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class MqopsDocumentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $file = "";
        if ($this->file) {
            $file = Storage::disk('s3')->temporaryUrl(
                $this->file,
                Carbon::now()->addMinutes(10)
            );
        }
        return [
            'id' => $this->id,
            'mqops_type' => $this->mqops_type,
            'parent_id' => $this->parent_id,
            'file_url' => $file,
            'file_path' => $this->file
        ];
    }
}
