<?php

namespace App\Http\Resources\v1;

use App\Models\Role;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
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
            'name' => ucwords(str_replace('-', ' ', $this->name)),
            'role_key' => $this->name,
            'guard_name' => $this->guard_name,
            'is_admin' => ($this->superset_type == Role::ADMINTYPE) ? Role::IS_ADMIN : Role::IS_NOT_ADMIN,
            'type' => $this->type,
            'description' => $this->description,
            'status' => $this->status,
            'need_approval' => $this->need_approval,
            'permissions' => $this->permissions,
        ];
    }
}
