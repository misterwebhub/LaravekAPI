<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ApprovalResource extends JsonResource
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
            'name' => ($this->referenceUser->deleted_at == null) ? $this->referenceUser->name ?? "" : $this->referenceUser->name .' (Deleted User)',
            'action' => $this->title,
            'role' => ($this->referenceUser->roles->first()->name) ?? "",
            'status' => $this->status,
        ];
    }
}
