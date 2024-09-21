<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Resources\Json\JsonResource;

class MqopsActivityMediumResource extends JsonResource
{
     /* Transform the resource into an array.
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
            'old_data_id' => $this->old_data_id,
        ];
    }
}
