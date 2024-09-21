<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\MasterTrainerUser;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        if (isset($request->master_trainer_id) && $request->master_trainer_id) {
            $isSelected = (MasterTrainerUser::where('user_id', $this->id)
                ->where('master_trainer_id', $request->master_trainer_id)->count() > 0) ? true : false;
        }
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'mobile' => $this->mobile,
            'account_type' => $this->roles()->first()->name ?? null,
            'status' => $this->status,
            'organisation_id' => $this->organisation_id,
            'organisation' => $this->organisation->name ?? null,
            'centre_id' => $this->centre_id,
            'centre' => $this->centre->name ?? null,
            'program_id' => $this->program_id,
            'program' => $this->program->name ?? null,
            'project_id' => $this->project_id,
            'project' => $this->project->name ?? null,
            'is_quest_employee' => $this->is_quest_employee ?? null,
            'mqops_access' => $this->hasPermissionTo('mqops.access') ?? null,
            'is_selected_facilitator' => $isSelected ?? null,
        ];
    }
}
