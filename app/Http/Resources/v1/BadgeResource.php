<?php

namespace App\Http\Resources\v1;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class BadgeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $image = "";
        if ($this->image) {
            $image = Storage::disk('s3')->temporaryUrl(
                $this->image,
                Carbon::now()->addMinutes(1)
            );
        }
        return [
            'id' => $this->id,
            'name' => $this->name,
            'image' => $image,
            'point' => $this->point,
        ];
    }
}
