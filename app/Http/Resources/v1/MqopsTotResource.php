<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Resources\Json\JsonResource;

class MqopsTotResource extends JsonResource
{
    /* Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        if (!$this->tot_id) {
            return parent::toArray($request);
        }
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user_name' => $this->user->name,
            'mode' => $this->mode,
            'mode_name' => config('mqops.mode.' . $this->mode),
            'venue_tot' => $this->venue_tot,
            'ecosystem_id' => (int)$this->ecosystem_id,
            'ecosystem_name' => config('mqops.ecosystem.' . $this->ecosystem_id),
            'other_ecosystem' => $this->other_ecosystem,
            'tot_id' => $this->tot_id,
            'tot_name' => $this->totType->name,
            'other_tot' => $this->other_tot,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'details' => MqopsTotDetailResource::collection($this->details()->where('state_id', '!=', '')->whereNotnull('state_id')->get())
        ];
    }
}
