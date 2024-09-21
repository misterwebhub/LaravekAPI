<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Resources\Json\JsonResource;

class ActivityLogResource extends JsonResource
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
            'user_id' => $this->causer_id,
            'name' => $this->user_name ?? null,
            'role' => $this->role_name ?? null,
            'description' => $this->description,
            'log_name' => $this->log_name,
            'table_id' => $this->subject_id,
            'created_date' => $this->created_at->format('Y-m-d H:i:s'),
            'properties' => $this->properties,
        ];
    }
}
