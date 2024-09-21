<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Resources\Json\JsonResource;

class ProjectsToCentreResource extends JsonResource
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
            'project_name' => $this->name,
            'program_name' => $this->program->name,
            'program_id' => $this->program->id,
            'phases' => $this->phases,
            'phasenames' => $this->phasenames,
        ];
    }
}
