<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Resources\Json\JsonResource;

class PhaseResource extends JsonResource
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
        if (!empty($this->start_date)) {
            $startDateReadable = $this->start_date->format(config('app.date_format'));
        } else {
            $startDateReadable = null;
        }
        if (!empty($this->end_date)) {
            $endDateReadable = $this->end_date->format(config('app.date_format'));
        } else {
            $endDateReadable = null;
        }
        return [
            'id' => $this->id,
            'name' => $this->name,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'start_date_readable' => $startDateReadable,
            'end_date_readable' => $endDateReadable,
            'target_students' => $this->target_students,
            'target_trainers' => $this->target_trainers,
        ];
    }
}
